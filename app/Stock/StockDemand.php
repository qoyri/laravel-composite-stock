<?php

declare(strict_types=1);

namespace App\Stock;

use App\Models\ArticleVariant;
use App\Models\Marking;
use Illuminate\Support\Collection;

/**
 * Quantities requested per stock component, summed over all order lines.
 *
 * Checking line by line is wrong: "blue M + logo A" and "blue M + logo B"
 * each look fine against a stock of 3, yet together they need 4 shirts.
 */
final class StockDemand
{
    /** @var array<int, int> variant id => items */
    private array $variants = [];

    /** @var array<int, int> marking id => consumable units */
    private array $markings = [];

    public function add(int $variantId, int $markingId, int $quantity, int $unitsPerItem): void
    {
        $this->variants[$variantId] = ($this->variants[$variantId] ?? 0) + $quantity;
        $this->markings[$markingId] = ($this->markings[$markingId] ?? 0) + $quantity * $unitsPerItem;
    }

    /** @return array<int, int> */
    public function variants(): array
    {
        return $this->variants;
    }

    /** @return array<int, int> */
    public function markings(): array
    {
        return $this->markings;
    }

    /**
     * Compares the demand with the given rows, which the caller must have
     * locked. Variants need their `article` loaded for the labels.
     *
     * @param  Collection<int, ArticleVariant>  $variants  keyed by id
     * @param  Collection<int, Marking>  $markings  keyed by id
     * @return list<Shortage>
     */
    public function shortages(Collection $variants, Collection $markings): array
    {
        $shortages = [];

        foreach ($this->variants as $id => $requested) {
            $variant = $variants->get($id);
            if ($variant !== null && $variant->stock < $requested) {
                $shortages[] = new Shortage('variant', $variant->article->name.' '.$variant->label(), $requested, $variant->stock);
            }
        }

        foreach ($this->markings as $id => $requested) {
            $marking = $markings->get($id);
            if ($marking !== null && ! $marking->is_unlimited && $marking->stock < $requested) {
                $shortages[] = new Shortage('marking', $marking->name, $requested, $marking->stock);
            }
        }

        return $shortages;
    }
}
