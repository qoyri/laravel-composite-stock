<?php

declare(strict_types=1);

namespace App\Stock;

use DomainException;

final class InvalidStockAdjustment extends DomainException
{
    public static function zero(): self
    {
        return new self('Un ajustement de 0 ne change rien.');
    }

    public static function unlimited(): self
    {
        return new self('Ce marquage est illimité : il n\'a pas de stock à ajuster.');
    }

    public static function belowZero(int $stock, int $delta): self
    {
        return new self("Stock actuel {$stock} : impossible de retirer ".abs($delta).'.');
    }
}
