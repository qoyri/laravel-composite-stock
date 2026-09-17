<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Swiss price formatting: 35.– CHF, 35.50 CHF, 1'250.– CHF.
 */
final class Chf
{
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $francs = number_format(intdiv($cents, 100), 0, '.', "'");
        $rappen = $cents % 100;

        // Non-breaking space: an amount never wraps away from its currency.
        return $sign.$francs.'.'.($rappen === 0 ? '–' : sprintf('%02d', $rappen))."\u{00A0}CHF";
    }
}
