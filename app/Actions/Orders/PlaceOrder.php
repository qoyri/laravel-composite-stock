<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Cart\CartLine;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Jobs\SendOrderConfirmation;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use App\Models\Product;
use App\Orders\CustomerDetails;
use App\Orders\OrderReference;
use App\Orders\ShippingFee;
use App\Stock\InsufficientStock;
use App\Stock\Shortage;
use App\Stock\StockDemand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Turns a cart into an order, decrementing both stock components atomically.
 *
 * Concurrency model (PostgreSQL):
 *  1. products        FOR SHARE   — price and units_per_item cannot change mid-checkout,
 *                                   other checkouts of the same product are not blocked;
 *  2. article_variants FOR UPDATE — ordered by id;
 *  3. markings         FOR UPDATE — ordered by id.
 * Every writer takes its locks in this order, so two checkouts wait for each
 * other instead of deadlocking. The second one re-reads the stock after the
 * first commits and fails cleanly with InsufficientStock.
 */
final readonly class PlaceOrder
{
    /** Retries of the whole transaction if PostgreSQL still reports a deadlock. */
    private const ATTEMPTS = 3;

    public function __construct(
        private ShippingFee $shippingFee,
        private OrderReference $references,
    ) {}

    /**
     * @param  list<CartLine>  $lines
     *
     * @throws InsufficientStock when any component cannot cover the demand
     */
    public function handle(array $lines, CustomerDetails $customer): Order
    {
        if ($lines === []) {
            throw new InvalidArgumentException('Cannot place an empty order.');
        }

        // Not left to the callers' validation: a negative quantity would turn
        // the decrement into a restock.
        foreach ($lines as $line) {
            if ($line->quantity < 1) {
                throw new InvalidArgumentException("Invalid quantity {$line->quantity} for cart line {$line->key()}.");
            }
        }

        return DB::transaction(function () use ($lines, $customer): Order {
            $products = Product::query()
                ->with('article')
                ->whereKey(array_map(fn (CartLine $l) => $l->productId, $lines))
                ->orderBy('id')
                ->sharedLock()
                ->get()
                ->keyBy('id');

            $variants = ArticleVariant::query()
                ->whereKey(array_map(fn (CartLine $l) => $l->variantId, $lines))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $markings = Marking::query()
                ->whereKey($products->pluck('marking_id')->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Labels only; articles need no lock.
            $variants->load('article');

            [$demand, $shortages] = $this->collectDemand($lines, $products, $variants, $markings);
            $shortages = [...$shortages, ...$demand->shortages($variants, $markings)];

            if ($shortages !== []) {
                throw new InsufficientStock($shortages);
            }

            $order = $this->createOrder($lines, $products, $variants, $markings, $customer);
            $this->decrement($demand, $variants, $markings, $order);

            SendOrderConfirmation::dispatch($order)->afterCommit();

            return $order;
        }, self::ATTEMPTS);
    }

    /**
     * @param  list<CartLine>  $lines
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ArticleVariant>  $variants
     * @param  Collection<int, Marking>  $markings
     * @return array{StockDemand, list<Shortage>}
     */
    private function collectDemand(array $lines, Collection $products, Collection $variants, Collection $markings): array
    {
        $demand = new StockDemand;
        $shortages = [];

        foreach ($lines as $line) {
            $product = $products->get($line->productId);
            $variant = $variants->get($line->variantId);
            $marking = $product !== null ? $markings->get($product->marking_id) : null;

            $sellable = $product !== null && $variant !== null && $marking !== null
                && $product->is_active && $product->article->is_active && $marking->is_active
                && $variant->article_id === $product->article_id;

            if (! $sellable) {
                $label = $product !== null && $marking !== null
                    ? $product->setRelation('marking', $marking)->displayName()
                    : 'Produit #'.$line->productId;
                $shortages[] = Shortage::unavailableProduct($label, $line->quantity);

                continue;
            }

            $demand->add($variant->id, $marking->id, $line->quantity, $product->units_per_item);
        }

        return [$demand, $shortages];
    }

    /**
     * @param  list<CartLine>  $lines
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ArticleVariant>  $variants
     * @param  Collection<int, Marking>  $markings
     */
    private function createOrder(array $lines, Collection $products, Collection $variants, Collection $markings, CustomerDetails $customer): Order
    {
        $rows = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            /** @var Product $product */
            $product = $products->get($line->productId);
            /** @var ArticleVariant $variant */
            $variant = $variants->get($line->variantId);
            /** @var Marking $marking */
            $marking = $markings->get($product->marking_id);

            $lineTotal = $product->price_cents * $line->quantity;
            $subtotal += $lineTotal;

            $rows[] = [
                'product_id' => $product->id,
                'article_id' => $product->article_id,
                'article_variant_id' => $variant->id,
                'marking_id' => $marking->id,
                'product_name' => $product->setRelation('marking', $marking)->displayName(),
                'variant_label' => $variant->label(),
                'unit_price_cents' => $product->price_cents,
                'quantity' => $line->quantity,
                'line_total_cents' => $lineTotal,
                'marking_units' => $marking->is_unlimited ? 0 : $line->quantity * $product->units_per_item,
            ];
        }

        $shipping = $this->shippingFee->for($subtotal);

        $order = Order::create([
            ...$customer->toOrderAttributes(),
            'reference' => $this->references->next(),
            'status' => OrderStatus::Confirmed,
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $subtotal + $shipping,
        ]);

        $order->lines()->createMany($rows);
        $order->load('lines');

        return $order;
    }

    /**
     * @param  Collection<int, ArticleVariant>  $variants
     * @param  Collection<int, Marking>  $markings
     */
    private function decrement(StockDemand $demand, Collection $variants, Collection $markings, Order $order): void
    {
        foreach ($demand->variants() as $id => $quantity) {
            /** @var ArticleVariant $variant */
            $variant = $variants->get($id);
            $variant->decrement('stock', $quantity);
            $variant->stockMovements()->create([
                'delta' => -$quantity,
                'stock_after' => $variant->stock,
                'reason' => StockMovementReason::Sale,
                'order_id' => $order->id,
            ]);
        }

        foreach ($demand->markings() as $id => $units) {
            /** @var Marking $marking */
            $marking = $markings->get($id);
            if ($marking->is_unlimited) {
                continue;
            }
            $marking->decrement('stock', $units);
            $marking->stockMovements()->create([
                'delta' => -$units,
                'stock_after' => $marking->stock,
                'reason' => StockMovementReason::Sale,
                'order_id' => $order->id,
            ]);
        }
    }
}
