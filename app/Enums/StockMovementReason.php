<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementReason: string
{
    case Sale = 'sale';
    case Cancellation = 'cancellation';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Vente',
            self::Cancellation => 'Annulation',
            self::Adjustment => 'Ajustement',
        };
    }
}
