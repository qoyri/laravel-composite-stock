<?php

declare(strict_types=1);

namespace App\Cart;

/**
 * What the customer asked for. No price and no stock: both are read again,
 * from the database, when the order is placed.
 */
final readonly class CartLine
{
    public function __construct(
        public int $productId,
        public int $variantId,
        public int $quantity,
    ) {}

    public function key(): string
    {
        return $this->productId.':'.$this->variantId;
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->productId, $this->variantId, $quantity);
    }
}
