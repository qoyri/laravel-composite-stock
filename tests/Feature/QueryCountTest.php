<?php

declare(strict_types=1);

use App\Actions\Orders\PlaceOrder;
use App\Models\ArticleVariant;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/*
 * N+1 guard. Two layers:
 *  - Model::shouldBeStrict() (AppServiceProvider) makes any lazy load throw
 *    outside production, so every page test above already fails on an N+1;
 *  - here, the number of queries of the list pages must not grow with the
 *    number of rows displayed.
 */

function queriesFor(Closure $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

function makeProducts(int $count): void
{
    foreach (range(1, $count) as $_) {
        [$product] = sellable();
        ArticleVariant::factory()->for($product->article)->count(2)->create();
    }
}

function makeOrders(int $count): void
{
    Queue::fake();
    foreach (range(1, $count) as $_) {
        [$product, $variant] = sellable();
        [$other, $otherVariant] = sellable();
        app(PlaceOrder::class)->handle([line($product, $variant), line($other, $otherVariant, 2)], customer());
    }
}

it('lists the catalogue with a constant number of queries', function () {
    makeProducts(3);
    $few = queriesFor(fn () => $this->get('/produits')->assertOk());

    makeProducts(9);   // 12 products: a full page
    $many = queriesFor(fn () => $this->get('/produits')->assertOk());

    expect(Product::count())->toBe(12)
        ->and($many)->toBe($few);
});

it('renders the home page with a constant number of queries', function () {
    makeProducts(2);
    $few = queriesFor(fn () => $this->get('/')->assertOk());

    makeProducts(8);
    $many = queriesFor(fn () => $this->get('/')->assertOk());

    expect($many)->toBe($few);
});

it('renders the cart with a constant number of queries', function () {
    $cart = app(App\Cart\Cart::class);
    [$product, $variant] = sellable();
    $cart->add(line($product, $variant));
    $few = queriesFor(fn () => $this->get('/panier')->assertOk());

    foreach (range(1, 6) as $_) {
        [$product, $variant] = sellable();
        app(App\Cart\Cart::class)->add(line($product, $variant));
    }
    $many = queriesFor(fn () => $this->get('/panier')->assertOk());

    expect($many)->toBe($few);
});

it('lists orders and products in the back-office with a constant number of queries', function (string $uri) {
    $this->actingAs(User::factory()->admin()->create());

    makeOrders(2);
    $few = queriesFor(fn () => $this->get($uri)->assertOk());

    makeOrders(8);
    $many = queriesFor(fn () => $this->get($uri)->assertOk());

    expect($many)->toBe($few);
})->with(['/admin', '/admin/commandes', '/admin/produits', '/admin/articles', '/admin/marquages', '/admin/stock']);

it('places an order with a number of queries that depends on distinct components, not on quantities', function () {
    Queue::fake();
    [$product, $variant] = sellable(variantStock: 50, markingStock: 50);

    $one = queriesFor(fn () => app(PlaceOrder::class)->handle([line($product, $variant, 1)], customer()));
    $ten = queriesFor(fn () => app(PlaceOrder::class)->handle([line($product, $variant, 10)], customer()));

    expect($ten)->toBe($one);
});
