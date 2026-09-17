<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A sellable product = one article + one marking.
 *
 * This is the custom pivot model of Article <-> Marking. It keeps an
 * auto-incrementing id so that routes and order lines can reference it.
 * It deliberately has no stock column: availability is computed from the
 * two components (see App\Stock\AvailabilityCalculator).
 *
 * @property int $id
 * @property int $article_id
 * @property int $marking_id
 * @property string $slug
 * @property int $price_cents
 * @property int $units_per_item
 * @property bool $is_active
 * @property-read Article $article
 * @property-read Marking $marking
 */
#[Fillable(['article_id', 'marking_id', 'slug', 'price_cents', 'units_per_item', 'is_active'])]
#[RouteKey('slug')]
class Product extends Pivot
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public $incrementing = true;

    protected $table = 'products';

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<Marking, $this> */
    public function marking(): BelongsTo
    {
        return $this->belongsTo(Marking::class);
    }

    /**
     * The key must be explicit: on a Pivot, getForeignKey() returns the pivot's
     * parent key (null here), not "product_id".
     *
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'product_id');
    }

    /**
     * Active product whose article and marking are active too.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sellable(Builder $query): void
    {
        $query->where('products.is_active', true)
            ->whereHas('article', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('marking', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * At least one variant can be sold: the SQL twin of AvailabilityCalculator.
     * min(variant stock, marking capacity) > 0 for some variant
     *   ⇔ some variant has stock > 0 AND (marking unlimited OR stock >= units_per_item).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->whereHas('article.variants', fn (Builder $q) => $q->where('article_variants.stock', '>', 0))
            ->whereHas('marking', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('markings.is_unlimited', true)
                ->orWhereColumn('markings.stock', '>=', 'products.units_per_item')));
    }

    /** "J'habite chez mon chat — Hoodie". Needs `article` and `marking` loaded. */
    public function displayName(): string
    {
        return $this->marking->name.' — '.$this->article->name;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'units_per_item' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
