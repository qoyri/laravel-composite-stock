<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementReason;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\User;
use App\Stock\InvalidStockAdjustment;
use Illuminate\Support\Facades\DB;

/**
 * Manual stock correction from the back-office (goods received, damaged...).
 *
 * Takes a signed delta, never an absolute value: "set stock to 15" silently
 * erases any sale made since the form was opened. The row is locked so the
 * check-then-write cannot interleave with a checkout.
 */
final class AdjustStock
{
    /**
     * @throws InvalidStockAdjustment
     */
    public function handle(ArticleVariant|Marking $stockable, int $delta, User $by, ?string $note = null): ArticleVariant|Marking
    {
        if ($delta === 0) {
            throw InvalidStockAdjustment::zero();
        }

        return DB::transaction(function () use ($stockable, $delta, $by, $note): ArticleVariant|Marking {
            $locked = $stockable->newQuery()->whereKey($stockable->getKey())->lockForUpdate()->firstOrFail();

            if ($locked instanceof Marking && $locked->is_unlimited) {
                throw InvalidStockAdjustment::unlimited();
            }

            if ($locked->stock + $delta < 0) {
                throw InvalidStockAdjustment::belowZero($locked->stock, $delta);
            }

            $locked->increment('stock', $delta);
            $locked->stockMovements()->create([
                'delta' => $delta,
                'stock_after' => $locked->stock,
                'reason' => StockMovementReason::Adjustment,
                'user_id' => $by->id,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}
