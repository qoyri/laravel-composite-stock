<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Size;
use Database\Factories\ArticleVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Colour × size of an article: the first stock component of a sale.
 *
 * @property int $id
 * @property int $article_id
 * @property string $color_name
 * @property string $color_hex
 * @property Size $size
 * @property string $sku
 * @property int $stock
 * @property-read Article $article
 */
#[Fillable(['article_id', 'color_name', 'color_hex', 'size', 'sku', 'stock'])]
class ArticleVariant extends Model
{
    /** @use HasFactory<ArticleVariantFactory> */
    use HasFactory;

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return MorphMany<StockMovement, $this> */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable');
    }

    public function label(): string
    {
        return $this->color_name.' / '.$this->size->label();
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->where('article_variants.stock', '>', 0);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => Size::class,
            'stock' => 'integer',
        ];
    }
}
