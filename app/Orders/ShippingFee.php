<?php

declare(strict_types=1);

namespace App\Orders;

use Illuminate\Container\Attributes\Config;

final readonly class ShippingFee
{
    public function __construct(
        #[Config('shop.shipping.fee_cents')] private int $feeCents,
        #[Config('shop.shipping.free_from_cents')] private int $freeFromCents,
    ) {}

    public function for(int $subtotalCents): int
    {
        return $subtotalCents >= $this->freeFromCents ? 0 : $this->feeCents;
    }

    /** Amount still missing to get free shipping, 0 once reached. */
    public function missingForFreeShipping(int $subtotalCents): int
    {
        return max(0, $this->freeFromCents - $subtotalCents);
    }
}
