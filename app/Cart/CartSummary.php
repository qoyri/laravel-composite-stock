<?php

declare(strict_types=1);

namespace App\Cart;

final readonly class CartSummary
{
    /**
     * @param  list<CartItem>  $items
     */
    public function __construct(
        public array $items,
        public int $subtotalCents,
        public int $shippingCents,
        public int $missingForFreeShippingCents,
    ) {}

    public function totalCents(): int
    {
        return $this->subtotalCents + $this->shippingCents;
    }

    public function count(): int
    {
        return array_sum(array_map(fn (CartItem $item) => $item->quantity, $this->items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function hasProblems(): bool
    {
        foreach ($this->items as $item) {
            if ($item->hasProblem()) {
                return true;
            }
        }

        return false;
    }
}
