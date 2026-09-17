<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmée',
            self::Cancelled => 'Annulée',
        };
    }
}
