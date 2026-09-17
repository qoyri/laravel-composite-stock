<?php

declare(strict_types=1);

use App\Catalog\ProductCatalog;
use App\Models\Product;
use Database\Seeders\CatalogueSeeder;

it('agrees with AvailabilityCalculator on every seeded product', function () {
    // Product::available() re-expresses the calculator in SQL for filtering.
    // Two implementations of one rule must be checked against each other.
    $this->seed(CatalogueSeeder::class);

    $inSql = Product::query()->available()->pluck('id')->sort()->values()->all();
    $inPhp = Product::query()->with(['article.variants', 'marking'])->get()
        ->filter(fn (Product $p) => app(ProductCatalog::class)->availabilityOf($p) > 0)
        ->pluck('id')->sort()->values()->all();

    expect($inSql)->toBe($inPhp)
        ->and(count($inSql))->toBeLessThan(Product::count());   // the seed does contain unavailable products
});
