<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ConcurrencyTestCase;
use Tests\TestCase;

/*
 * Every test boots the framework. Feature tests run inside a transaction that
 * is rolled back afterwards (RefreshDatabase) — except the concurrency tests,
 * which need committed data visible to other connections and opt into
 * DatabaseTruncation themselves.
 */
pest()->extend(TestCase::class)->in('Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(ConcurrencyTestCase::class)
    ->group('concurrency')
    ->in('Concurrency');

/*
 * Shared builders for stock scenarios.
 */

use App\Cart\CartLine;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Product;
use App\Orders\CustomerDetails;

function customer(): CustomerDetails
{
    return new CustomerDetails(
        email: 'lea.muller@example.ch',
        firstName: 'Léa',
        lastName: 'Müller',
        phone: null,
        addressLine: 'Rue du Marché 12',
        postalCode: '1204',
        city: 'Genève',
    );
}

/**
 * One article, one variant, one marking and the product joining them.
 *
 * @return array{Product, ArticleVariant, Marking}
 */
function sellable(int $variantStock = 10, int $markingStock = 10, bool $unlimited = false, int $unitsPerItem = 1, int $priceCents = 3500): array
{
    $article = Article::factory()->create();
    $variant = ArticleVariant::factory()->for($article)->stock($variantStock)->create();
    $marking = $unlimited
        ? Marking::factory()->unlimited()->create()
        : Marking::factory()->stock($markingStock)->create();
    $product = Product::factory()
        ->for($article)->for($marking)
        ->price($priceCents)->unitsPerItem($unitsPerItem)
        ->create();

    return [$product, $variant, $marking];
}

function line(Product $product, ArticleVariant $variant, int $quantity = 1): CartLine
{
    return new CartLine($product->id, $variant->id, $quantity);
}
