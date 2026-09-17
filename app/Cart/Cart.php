<?php

declare(strict_types=1);

namespace App\Cart;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Session\Session;

/**
 * The customer's cart, kept in the session (no customer accounts).
 *
 * Stores identifiers and quantities only. Prices and availability are always
 * read again from the database — at display time by CartSummarizer, and
 * under lock by PlaceOrder.
 */
#[Scoped]
final class Cart
{
    private const KEY = 'cart.lines';

    public function __construct(private readonly Session $session) {}

    /** @return list<CartLine> */
    public function lines(): array
    {
        /** @var array<string, array{int, int, int}> $raw */
        $raw = $this->session->get(self::KEY, []);

        return array_values(array_map(
            fn (array $row) => new CartLine($row[0], $row[1], $row[2]),
            $raw,
        ));
    }

    public function find(string $key): ?CartLine
    {
        foreach ($this->lines() as $line) {
            if ($line->key() === $key) {
                return $line;
            }
        }

        return null;
    }

    public function quantityOf(int $productId, int $variantId): int
    {
        return $this->find($productId.':'.$variantId)->quantity ?? 0;
    }

    public function add(CartLine $line): void
    {
        $existing = $this->find($line->key());

        $this->put($existing === null ? $line : $existing->withQuantity($existing->quantity + $line->quantity));
    }

    public function update(string $key, int $quantity): void
    {
        $line = $this->find($key);

        if ($line === null) {
            return;
        }

        $quantity > 0 ? $this->put($line->withQuantity($quantity)) : $this->remove($key);
    }

    public function remove(string $key): void
    {
        $this->session->forget(self::KEY.'.'.$key);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function isEmpty(): bool
    {
        return $this->lines() === [];
    }

    private function put(CartLine $line): void
    {
        $this->session->put(self::KEY.'.'.$line->key(), [$line->productId, $line->variantId, $line->quantity]);
    }
}
