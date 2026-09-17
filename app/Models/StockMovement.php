<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only audit row for a stock change on either component type.
 *
 * @property int $id
 * @property string $stockable_type
 * @property int $stockable_id
 * @property int $delta
 * @property int $stock_after
 * @property StockMovementReason $reason
 * @property int|null $order_id
 * @property int|null $user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read ArticleVariant|Marking $stockable
 * @property-read Order|null $order
 * @property-read User|null $user
 */
#[Fillable(['stockable_type', 'stockable_id', 'delta', 'stock_after', 'reason', 'order_id', 'user_id', 'note'])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    /** @return MorphTo<Model, $this> */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'stock_after' => 'integer',
            'reason' => StockMovementReason::class,
        ];
    }
}
