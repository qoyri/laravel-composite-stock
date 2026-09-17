<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Shape drawn by <x-garment-preview>: the shop has no product photography.
 */
enum Silhouette: string
{
    case TShirt = 'tshirt';
    case LongSleeve = 'long_sleeve';
    case Tank = 'tank';
    case Hoodie = 'hoodie';
    case Sweatshirt = 'sweatshirt';
    case Bodysuit = 'bodysuit';
    case ToteBag = 'tote_bag';
    case Cap = 'cap';

    public function label(): string
    {
        return match ($this) {
            self::TShirt => 'T-shirt',
            self::LongSleeve => 'Manches longues',
            self::Tank => 'Débardeur',
            self::Hoodie => 'Hoodie',
            self::Sweatshirt => 'Sweat',
            self::Bodysuit => 'Body',
            self::ToteBag => 'Tote bag',
            self::Cap => 'Casquette',
        };
    }
}
