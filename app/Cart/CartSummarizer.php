<?php

declare(strict_types=1);

namespace App\Cart;

use App\Models\ArticleVariant;
use App\Models\Product;
use App\Orders\ShippingFee;
use App\Stock\AvailabilityCalculator;
use Illuminate\Container\Attributes\Scoped;

/**
 * Resolves the session cart against the catalogue: two queries whatever the
 * number of lines. Memoised for the request, since the header, the drawer
 * and the cart page all need it.
 */
#[Scoped]
final class CartSummarizer
{
    private ?CartSummary $summary = null;

    private string $fingerprint = '';

    public function __construct(
        private readonly Cart $cart,
        private readonly AvailabilityCalculator $availability,
        private readonly ShippingFee $shippingFee,
    ) {}

    public function summarize(): CartSummary
    {
        $lines = $this->cart->lines();
        $fingerprint = serialize($lines);

        if ($this->summary !== null && $fingerprint === $this->fingerprint) {
            return $this->summary;
        }

        $products = Product::query()
            ->with(['article', 'marking'])
            ->whereKey(array_map(fn (CartLine $l) => $l->productId, $lines))
            ->get()
            ->keyBy('id');
        $variants = ArticleVariant::query()
            ->whereKey(array_map(fn (CartLine $l) => $l->variantId, $lines))
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            $product = $products->get($line->productId);
            $variant = $variants->get($line->variantId);

            // Deleted from the catalogue since: nothing meaningful to display.
            if ($product === null || $variant === null || $variant->article_id !== $product->article_id) {
                continue;
            }

            $sellable = $product->is_active && $product->article->is_active && $product->marking->is_active;
            $item = new CartItem(
                key: $line->key(),
                product: $product,
                variant: $variant,
                quantity: $line->quantity,
                available: $sellable ? $this->availability->quantity($product, $variant) : 0,
                sellable: $sellable,
            );

            $items[] = $item;
            $subtotal += $item->lineTotalCents();
        }

        $this->fingerprint = $fingerprint;

        return $this->summary = new CartSummary(
            items: $items,
            subtotalCents: $subtotal,
            shippingCents: $items === [] ? 0 : $this->shippingFee->for($subtotal),
            missingForFreeShippingCents: $this->shippingFee->missingForFreeShipping($subtotal),
        );
    }
}
