<?php

declare(strict_types=1);

namespace App\Stock;

use RuntimeException;

/**
 * Thrown inside the order transaction, which rolls everything back.
 * Carries every shortage found, so the customer fixes the cart in one go.
 */
final class InsufficientStock extends RuntimeException
{
    /**
     * @param  list<Shortage>  $shortages
     */
    public function __construct(public readonly array $shortages)
    {
        parent::__construct(implode(' ', array_map(fn (Shortage $s) => $s->message(), $shortages)));
    }
}
