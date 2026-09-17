<?php

declare(strict_types=1);

use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Product;
use App\Stock\AvailabilityCalculator;

/**
 * Builds an unsaved product/variant pair: the calculator is pure and never queries.
 */
function pair(int $variantStock, int $markingStock, bool $unlimited = false, int $unitsPerItem = 1): array
{
    $marking = new Marking(['stock' => $markingStock, 'is_unlimited' => $unlimited]);
    $product = new Product(['article_id' => 1, 'units_per_item' => $unitsPerItem]);
    $product->setRelation('marking', $marking);
    $variant = new ArticleVariant(['article_id' => 1, 'stock' => $variantStock]);

    return [$product, $variant];
}

it('is the minimum of both component stocks', function (int $variantStock, int $markingStock, int $expected) {
    [$product, $variant] = pair($variantStock, $markingStock);

    expect((new AvailabilityCalculator)->quantity($product, $variant))->toBe($expected);
})->with([
    'textile is the bottleneck' => [3, 40, 3],
    'marking is the bottleneck' => [40, 2, 2],
    'both equal' => [7, 7, 7],
    'textile sold out' => [0, 40, 0],
    'marking sold out' => [40, 0, 0],
]);

it('ignores the marking stock when the marking is unlimited', function () {
    [$product, $variant] = pair(variantStock: 12, markingStock: 0, unlimited: true);

    expect((new AvailabilityCalculator)->quantity($product, $variant))->toBe(12);
});

it('divides the marking stock by the units consumed per item, rounding down', function () {
    // 7 transfers, 2 per shirt: only 3 shirts can be marked.
    [$product, $variant] = pair(variantStock: 50, markingStock: 7, unitsPerItem: 2);

    expect((new AvailabilityCalculator)->quantity($product, $variant))->toBe(3);
});

it('refuses a variant that belongs to another article', function () {
    [$product] = pair(10, 10);
    $foreignVariant = new ArticleVariant(['article_id' => 2, 'stock' => 10]);

    (new AvailabilityCalculator)->quantity($product, $foreignVariant);
})->throws(LogicException::class);

it('builds the availability matrix of every variant of the product', function () {
    [$product] = pair(0, 5);
    $variants = collect([
        tap(new ArticleVariant(['article_id' => 1, 'stock' => 9]), fn ($v) => $v->id = 10),
        tap(new ArticleVariant(['article_id' => 1, 'stock' => 2]), fn ($v) => $v->id = 11),
        tap(new ArticleVariant(['article_id' => 1, 'stock' => 0]), fn ($v) => $v->id = 12),
    ]);

    expect((new AvailabilityCalculator)->matrix($product, $variants))
        ->toBe([10 => 5, 11 => 2, 12 => 0]);
});
