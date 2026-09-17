<?php

declare(strict_types=1);

namespace App\Enums;

enum Size: string
{
    case Months3 = '3M';
    case Months6 = '6M';
    case Months12 = '12M';
    case Months18 = '18M';
    case Years2 = '2A';
    case Years4 = '4A';
    case Years6 = '6A';
    case Years8 = '8A';
    case Years10 = '10A';
    case Years12 = '12A';
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';
    case XXXL = '3XL';
    case OneSize = 'TU';

    public function label(): string
    {
        return match ($this) {
            self::Months3, self::Months6, self::Months12, self::Months18 => rtrim($this->value, 'M').' mois',
            self::Years2, self::Years4, self::Years6, self::Years8, self::Years10, self::Years12 => rtrim($this->value, 'A').' ans',
            self::OneSize => 'Taille unique',
            default => $this->value,
        };
    }

    /** Position in a size chart, so sizes never sort alphabetically (L < M < S). */
    public function rank(): int
    {
        return (int) array_search($this, self::cases(), true);
    }

    /** @return list<self> */
    public static function adult(): array
    {
        return [self::XS, self::S, self::M, self::L, self::XL, self::XXL, self::XXXL];
    }

    /** @return list<self> */
    public static function kids(): array
    {
        return [self::Years2, self::Years4, self::Years6, self::Years8, self::Years10, self::Years12];
    }

    /** @return list<self> */
    public static function baby(): array
    {
        return [self::Months3, self::Months6, self::Months12, self::Months18];
    }
}
