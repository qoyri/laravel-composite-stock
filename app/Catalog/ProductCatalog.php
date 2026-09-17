<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Product;
use App\Stock\AvailabilityCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side of the storefront. Every listing eager-loads what the cards need,
 * so the number of queries does not depend on the number of products.
 */
final readonly class ProductCatalog
{
    public const PER_PAGE = 12;

    public function __construct(private AvailabilityCalculator $availability) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(CatalogFilters $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->sellable()
            ->with(['article.category', 'article.variants', 'marking'])
            ->when($filters->category, fn (Builder $q, string $slug) => $q->whereRelation('article.category', 'slug', $slug))
            ->when($filters->marking, fn (Builder $q, string $slug) => $q->whereRelation('marking', 'slug', $slug))
            ->when($filters->technique, fn (Builder $q, $technique) => $q->whereRelation('marking', 'technique', $technique))
            ->when($filters->search, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $q->whereRelation('article', 'name', 'ilike', $like)
                    ->orWhereRelation('marking', 'name', 'ilike', $like);
            }))
            ->when($filters->availableOnly, fn (Builder $q) => $q->available());

        match ($filters->sort) {
            CatalogSort::PriceAsc => $query->orderBy('price_cents')->orderBy('id'),
            CatalogSort::PriceDesc => $query->orderByDesc('price_cents')->orderBy('id'),
            CatalogSort::Newest => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @return Collection<int, Product>
     */
    public function featured(int $limit = 8): Collection
    {
        return Product::query()
            ->sellable()
            ->available()
            ->with(['article.variants', 'marking'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Loads everything the product page needs, variants in size-chart order.
     */
    public function loadForPage(Product $product): Product
    {
        $product->load(['article.category', 'article.variants', 'marking']);
        $product->article->setRelation(
            'variants',
            $product->article->variants
                ->sortBy([['color_name', 'asc'], fn (ArticleVariant $a, ArticleVariant $b) => $a->size->rank() <=> $b->size->rank()])
                ->values(),
        );

        return $product;
    }

    /**
     * Same article with other markings.
     *
     * @return Collection<int, Product>
     */
    public function otherMarkingsOf(Article $article, Product $except, int $limit = 4): Collection
    {
        return $article->products()
            ->sellable()
            ->whereKeyNot($except->id)
            ->with(['article.variants', 'marking'])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Same marking on other articles.
     *
     * @return Collection<int, Product>
     */
    public function otherArticlesWith(Marking $marking, Product $except, int $limit = 4): Collection
    {
        return $marking->products()
            ->sellable()
            ->whereKeyNot($except->id)
            ->with(['article.variants', 'marking'])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Best availability among the product's variants: 0 means sold out.
     * Requires `article.variants` and `marking` to be loaded.
     */
    public function availabilityOf(Product $product): int
    {
        $matrix = $this->availability->matrix($product, $product->article->variants);

        return $matrix === [] ? 0 : max($matrix);
    }
}
