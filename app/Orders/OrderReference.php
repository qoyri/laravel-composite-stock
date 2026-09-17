<?php

declare(strict_types=1);

namespace App\Orders;

use App\Models\Order;

/**
 * Human-friendly order references (AC-7KQ4M9XT). The alphabet has no 0/O/1/I
 * so references survive being read over the phone. Uniqueness is enforced by
 * the database; the retry only covers an astronomically unlikely collision.
 */
final class OrderReference
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function next(): string
    {
        do {
            $reference = 'AC-'.$this->random(8);
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }

    private function random(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
