<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarkingTechnique;
use Database\Factories\MarkingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A marking (embroidery, flocking, screen print): the second stock component.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property MarkingTechnique $technique
 * @property string $ink_color
 * @property string $ink_hex
 * @property bool $is_unlimited
 * @property int $stock
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 */
#[Fillable(['name', 'slug', 'technique', 'ink_color', 'ink_hex', 'is_unlimited', 'stock', 'is_active'])]
#[RouteKey('slug')]
class Marking extends Model
{
    /** @use HasFactory<MarkingFactory> */
    use HasFactory;

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return BelongsToMany<Article, $this, Product, 'offer'> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'products')
            ->using(Product::class)
            ->as('offer')
            ->withPivot(['id', 'slug', 'price_cents', 'units_per_item', 'is_active'])
            ->withTimestamps();
    }

    /** @return MorphMany<StockMovement, $this> */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable');
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('markings.is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technique' => MarkingTechnique::class,
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
            'stock' => 'integer',
        ];
    }
}
