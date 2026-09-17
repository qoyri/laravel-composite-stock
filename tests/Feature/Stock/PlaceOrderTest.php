<?php

declare(strict_types=1);

use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Jobs\SendOrderConfirmation;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Stock\InsufficientStock;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function placeOrder(array $lines): Order
{
    return app(PlaceOrder::class)->handle($lines, customer());
}

describe('a successful order', function () {
    it('decrements the textile variant and the marking', function () {
        [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 8);

        placeOrder([line($product, $variant, 3)]);

        expect($variant->fresh()->stock)->toBe(7)
            ->and($marking->fresh()->stock)->toBe(5);
    });

    it('consumes units_per_item marking units per item sold', function () {
        [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 10, unitsPerItem: 2);

        placeOrder([line($product, $variant, 3)]);

        expect($variant->fresh()->stock)->toBe(7)
            ->and($marking->fresh()->stock)->toBe(4);
    });

    it('stores the order with price snapshots and shipping', function () {
        [$product, $variant] = sellable(priceCents: 2000);

        $order = placeOrder([line($product, $variant, 2)]);

        expect($order->status)->toBe(OrderStatus::Confirmed)
            ->and($order->reference)->toStartWith('AC-')
            ->and($order->subtotal_cents)->toBe(4000)
            ->and($order->shipping_cents)->toBe(500)
            ->and($order->total_cents)->toBe(4500)
            ->and($order->lines)->toHaveCount(1);

        $line = $order->lines->first();
        expect($line->unit_price_cents)->toBe(2000)
            ->and($line->line_total_cents)->toBe(4000)
            ->and($line->marking_id)->toBe($product->marking_id)
            ->and($line->marking_units)->toBe(2)
            ->and($line->product_name)->toBe($product->load('article', 'marking')->displayName());
    });

    it('ships for free from 50 CHF', function () {
        [$product, $variant] = sellable(priceCents: 2500);

        $order = placeOrder([line($product, $variant, 2)]);

        expect($order->shipping_cents)->toBe(0)
            ->and($order->total_cents)->toBe(5000);
    });

    it('logs one stock movement per component with the resulting stock', function () {
        [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 8);

        $order = placeOrder([line($product, $variant, 3)]);

        $movements = StockMovement::where('order_id', $order->id)->get();
        expect($movements)->toHaveCount(2)
            ->and($movements->every(fn ($m) => $m->reason === StockMovementReason::Sale))->toBeTrue();

        $variantMove = $movements->firstWhere('stockable_type', 'article_variant');
        $markingMove = $movements->firstWhere('stockable_type', 'marking');
        expect([$variantMove->delta, $variantMove->stock_after])->toBe([-3, 7])
            ->and([$markingMove->delta, $markingMove->stock_after])->toBe([-3, 5]);
    });

    it('leaves an unlimited marking untouched', function () {
        [$product, $variant, $marking] = sellable(variantStock: 4, unlimited: true);

        $order = placeOrder([line($product, $variant, 4)]);

        expect($variant->fresh()->stock)->toBe(0)
            ->and($marking->fresh()->stock)->toBe(0)
            ->and(StockMovement::where('order_id', $order->id)->where('stockable_type', 'marking')->exists())->toBeFalse();
    });

    it('queues the confirmation email', function () {
        [$product, $variant] = sellable();

        $order = placeOrder([line($product, $variant)]);

        Queue::assertPushed(SendOrderConfirmation::class, fn ($job) => $job->order->is($order));
    });
});

describe('demand is aggregated per component, not per line', function () {
    it('counts two lines sharing the same textile variant together', function () {
        // Same blue M T-shirt with logo A and with logo B: 2 + 2 shirts, only 3 in stock.
        [$logoA, $variant] = sellable(variantStock: 3, markingStock: 10);
        $logoB = Product::factory()->for($variant->article)->create();

        expect(fn () => placeOrder([line($logoA, $variant, 2), line($logoB, $variant, 2)]))
            ->toThrow(InsufficientStock::class);

        expect($variant->fresh()->stock)->toBe(3);
    });

    it('counts two lines sharing the same marking together', function () {
        [$product, $variantM, $marking] = sellable(variantStock: 10, markingStock: 3);
        $variantL = ArticleVariant::factory()->for($variantM->article)->stock(10)->create();

        expect(fn () => placeOrder([line($product, $variantM, 2), line($product, $variantL, 2)]))
            ->toThrow(InsufficientStock::class);

        expect($marking->fresh()->stock)->toBe(3);
    });

    it('accepts the same lines when the shared stock suffices', function () {
        [$logoA, $variant] = sellable(variantStock: 4, markingStock: 10);
        $logoB = Product::factory()->for($variant->article)->create();

        placeOrder([line($logoA, $variant, 2), line($logoB, $variant, 2)]);

        expect($variant->fresh()->stock)->toBe(0);
    });
});

describe('a refused order', function () {
    it('is refused when the marking is exhausted, and nothing is written', function () {
        [$product, $variant, $marking] = sellable(variantStock: 10, markingStock: 0);

        try {
            placeOrder([line($product, $variant, 1)]);
            $this->fail('InsufficientStock was not thrown.');
        } catch (InsufficientStock $e) {
            expect($e->shortages)->toHaveCount(1)
                ->and($e->shortages[0]->component)->toBe('marking')
                ->and($e->shortages[0]->requested)->toBe(1)
                ->and($e->shortages[0]->available)->toBe(0);
        }

        expect($variant->fresh()->stock)->toBe(10)
            ->and(Order::count())->toBe(0)
            ->and(StockMovement::count())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('is refused when the variant sold out after being added to the cart', function () {
        [$product, $variant, $marking] = sellable(variantStock: 2, markingStock: 10);
        $cart = [line($product, $variant, 2)];   // valid when added

        $variant->update(['stock' => 1]);         // someone else bought one meanwhile

        expect(fn () => placeOrder($cart))->toThrow(InsufficientStock::class);
        expect($variant->fresh()->stock)->toBe(1)
            ->and($marking->fresh()->stock)->toBe(10)
            ->and(Order::count())->toBe(0);
    });

    it('lists every missing component, not only the first one', function () {
        [$product, $variant] = sellable(variantStock: 1, markingStock: 1);

        try {
            placeOrder([line($product, $variant, 2)]);
            $this->fail('InsufficientStock was not thrown.');
        } catch (InsufficientStock $e) {
            expect(collect($e->shortages)->pluck('component')->all())->toBe(['variant', 'marking']);
        }
    });

    it('rolls back the textile decrement when the marking check fails', function () {
        // Two lines: the first is fine, the second exhausts its marking.
        [$okProduct, $okVariant] = sellable(variantStock: 5, markingStock: 5);
        [$koProduct, $koVariant] = sellable(variantStock: 5, markingStock: 0);

        expect(fn () => placeOrder([line($okProduct, $okVariant, 1), line($koProduct, $koVariant, 1)]))
            ->toThrow(InsufficientStock::class);

        expect($okVariant->fresh()->stock)->toBe(5)
            ->and($okProduct->marking->fresh()->stock)->toBe(5);
    });

    it('is refused when the product was deactivated', function () {
        [$product, $variant] = sellable();
        $product->update(['is_active' => false]);

        expect(fn () => placeOrder([line($product, $variant)]))->toThrow(InsufficientStock::class);
    });

    it('is refused when the marking was deactivated', function () {
        [$product, $variant, $marking] = sellable();
        $marking->update(['is_active' => false]);

        expect(fn () => placeOrder([line($product, $variant)]))->toThrow(InsufficientStock::class);
    });

    it('refuses an empty cart', function () {
        placeOrder([]);
    })->throws(InvalidArgumentException::class);

    it('refuses a variant that does not belong to the product', function () {
        [$product] = sellable();
        [, $foreignVariant] = sellable();

        expect(fn () => placeOrder([line($product, $foreignVariant)]))->toThrow(InsufficientStock::class);
        expect(Order::count())->toBe(0);
    });
});

it('never sells more marking than it has when the same marking is on another article', function () {
    // One logo, two different textiles: the logo stock is shared.
    [$tshirt, $tshirtVariant, $logo] = sellable(variantStock: 10, markingStock: 2);
    [, $hoodieVariant] = sellable(variantStock: 10);
    $hoodie = Product::factory()->for($hoodieVariant->article)->for($logo)->create();

    placeOrder([line($tshirt, $tshirtVariant, 1)]);
    placeOrder([line($hoodie, $hoodieVariant, 1)]);

    expect($logo->fresh()->stock)->toBe(0)
        ->and(fn () => placeOrder([line($hoodie, $hoodieVariant, 1)]))->toThrow(InsufficientStock::class);
});
