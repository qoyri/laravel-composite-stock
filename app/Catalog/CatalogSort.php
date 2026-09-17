<?php

declare(strict_types=1);

namespace App\Catalog;

enum CatalogSort: string
{
    case Newest = 'nouveautes';
    case PriceAsc = 'prix-croissant';
    case PriceDesc = 'prix-decroissant';

    public function label(): string
    {
        return match ($this) {
            self::Newest => 'Nouveautés',
            self::PriceAsc => 'Prix croissant',
            self::PriceDesc => 'Prix décroissant',
        };
    }
}
