<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $article_id
 * @property int $article_variant_id
 * @property int $marking_id
 * @property string $product_name
 * @property string $variant_label
 * @property int $unit_price_cents
 * @property int $quantity
 * @property int $line_total_cents
 * @property int $marking_units
 * @property-read Order $order
 * @property-read Product $product
 * @property-read ArticleVariant $variant
 * @property-read Marking $marking
 */
#[Fillable([
    'order_id', 'product_id', 'article_id', 'article_variant_id', 'marking_id',
    'product_name', 'variant_label', 'unit_price_cents', 'quantity', 'line_total_cents', 'marking_units',
])]
class OrderLine extends Model
{
    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ArticleVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ArticleVariant::class, 'article_variant_id');
    }

    /** @return BelongsTo<Marking, $this> */
    public function marking(): BelongsTo
    {
        return $this->belongsTo(Marking::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
            'marking_units' => 'integer',
        ];
    }
}
