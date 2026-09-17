<?php

declare(strict_types=1);

namespace App\Cart;

use App\Models\ArticleVariant;
use App\Models\Product;

/**
 * A cart line resolved against the catalogue, for display.
 */
final readonly class CartItem
{
    public function __construct(
        public string $key,
        public Product $product,
        public ArticleVariant $variant,
        public int $quantity,
        public int $available,
        public bool $sellable,
    ) {}

    public function unitPriceCents(): int
    {
        return $this->product->price_cents;
    }

    public function lineTotalCents(): int
    {
        return $this->product->price_cents * $this->quantity;
    }

    /** The line cannot be ordered as is: the checkout would refuse it. */
    public function hasProblem(): bool
    {
        return ! $this->sellable || $this->quantity > $this->available;
    }
}
