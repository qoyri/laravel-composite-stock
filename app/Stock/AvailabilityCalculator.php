<?php

declare(strict_types=1);

namespace App\Stock;

use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Product;
use LogicException;

/**
 * How many units of a product can be sold in a given variant.
 *
 * Availability is never stored: it is derived, on read, from the two stock
 * components. The same formula serves the storefront (on eager-loaded models)
 * and PlaceOrder (on rows locked FOR UPDATE), so they cannot disagree.
 *
 * Pure: no queries. Callers must eager-load `marking` on the product.
 */
final class AvailabilityCalculator
{
    public function quantity(Product $product, ArticleVariant $variant): int
    {
        if ($variant->article_id !== $product->article_id) {
            throw new LogicException('The variant does not belong to the product\'s article.');
        }

        $capacity = $this->markingCapacity($product->marking, $product->units_per_item);

        // Deliberately not min($variant->stock, $capacity): with an unlimited
        // marking $capacity is null, and PHP's min() would return null.
        return $capacity === null ? $variant->stock : min($variant->stock, $capacity);
    }

    /**
     * Availability of each given variant, keyed by variant id.
     *
     * @param  iterable<ArticleVariant>  $variants
     * @return array<int, int>
     */
    public function matrix(Product $product, iterable $variants): array
    {
        $matrix = [];

        foreach ($variants as $variant) {
            $matrix[$variant->id] = $this->quantity($product, $variant);
        }

        return $matrix;
    }

    /**
     * Number of items the marking can still be applied to, or null when unlimited.
     */
    public function markingCapacity(Marking $marking, int $unitsPerItem): ?int
    {
        if ($marking->is_unlimited) {
            return null;
        }

        return intdiv($marking->stock, max(1, $unitsPerItem));
    }
}
