<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Silhouette;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A blank textile model (T-shirt, hoodie...). Stock lives on its variants.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string|null $material
 * @property Silhouette $silhouette
 * @property bool $is_active
 * @property-read Category $category
 * @property-read Collection<int, ArticleVariant> $variants
 * @property-read Collection<int, Product> $products
 */
#[Fillable(['category_id', 'name', 'slug', 'description', 'material', 'silhouette', 'is_active'])]
#[RouteKey('slug')]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ArticleVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ArticleVariant::class);
    }

    /**
     * The sellable products built on this article, as first-class models.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Same relationship seen as a many-to-many: the pivot row *is* the product
     * and carries the price of the pair and the consumables used per item.
     *
     * @return BelongsToMany<Marking, $this, Product, 'offer'>
     */
    public function markings(): BelongsToMany
    {
        return $this->belongsToMany(Marking::class, 'products')
            ->using(Product::class)
            ->as('offer')
            ->withPivot(['id', 'slug', 'price_cents', 'units_per_item', 'is_active'])
            ->withTimestamps();
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('articles.is_active', true);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function inCategory(Builder $query, Category $category): void
    {
        $query->where('articles.category_id', $category->id);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'silhouette' => Silhouette::class,
            'is_active' => 'boolean',
        ];
    }
}
