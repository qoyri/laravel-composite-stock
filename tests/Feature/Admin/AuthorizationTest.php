<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Every back-office route, with the parameters it needs.
 *
 * @return list<array{string, string, array<string, mixed>}>
 */
function adminRoutes(): array
{
    [$product, $variant, $marking] = sellable();
    $article = $product->article;
    $order = Order::factory()->create();

    return [
        ['GET', 'admin.dashboard', []],
        ['GET', 'admin.articles.index', []],
        ['GET', 'admin.articles.create', []],
        ['POST', 'admin.articles.store', []],
        ['GET', 'admin.articles.edit', ['article' => $article]],
        ['PUT', 'admin.articles.update', ['article' => $article]],
        ['DELETE', 'admin.articles.destroy', ['article' => $article]],
        ['POST', 'admin.articles.variants.store', ['article' => $article]],
        ['DELETE', 'admin.articles.variants.destroy', ['article' => $article, 'variant' => $variant]],
        ['GET', 'admin.markings.index', []],
        ['GET', 'admin.markings.create', []],
        ['POST', 'admin.markings.store', []],
        ['GET', 'admin.markings.edit', ['marking' => $marking]],
        ['PUT', 'admin.markings.update', ['marking' => $marking]],
        ['GET', 'admin.products.index', []],
        ['GET', 'admin.products.create', []],
        ['POST', 'admin.products.store', []],
        ['GET', 'admin.products.edit', ['product' => $product]],
        ['PUT', 'admin.products.update', ['product' => $product]],
        ['GET', 'admin.stock.index', []],
        ['POST', 'admin.stock.variants.adjust', ['variant' => $variant]],
        ['POST', 'admin.stock.markings.adjust', ['marking' => $marking]],
        ['GET', 'admin.orders.index', []],
        ['GET', 'admin.orders.show', ['order' => $order]],
        ['POST', 'admin.orders.cancel', ['order' => $order]],
        ['POST', 'admin.logout', []],
    ];
}

it('covers every back-office route', function () {
    $tested = collect(adminRoutes())->pluck(1)->sort()->values()->all();
    $declared = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name) => str_starts_with($name, 'admin.') && ! str_starts_with($name, 'admin.login'))
        ->sort()->values()->all();

    expect($tested)->toBe($declared);
});

it('redirects guests to the login page from every back-office route', function () {
    foreach (adminRoutes() as [$method, $name, $params]) {
        $this->call($method, route($name, $params))
            ->assertRedirect(route('admin.login'));
    }

    expect(Article::count())->toBe(1);  // nothing was created or deleted
});

describe('staff', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->staff()->create()));

    it('can read the back-office', function (string $name) {
        [$product, , $marking] = sellable();
        $params = match ($name) {
            'admin.articles.edit' => ['article' => $product->article],
            'admin.markings.edit' => ['marking' => $marking],
            'admin.orders.show' => ['order' => Order::factory()->create()],
            default => [],
        };

        $this->get(route($name, $params))->assertOk();
    })->with([
        'admin.dashboard', 'admin.articles.index', 'admin.articles.edit', 'admin.markings.index',
        'admin.markings.edit', 'admin.products.index', 'admin.stock.index', 'admin.orders.index', 'admin.orders.show',
    ]);

    it('can adjust stock', function () {
        [, $variant, $marking] = sellable(variantStock: 2, markingStock: 2);

        $this->post(route('admin.stock.variants.adjust', $variant), ['delta' => 5])->assertSessionHasNoErrors();
        $this->post(route('admin.stock.markings.adjust', $marking), ['delta' => -1, 'note' => 'Transfert abîmé'])->assertSessionHasNoErrors();

        expect($variant->fresh()->stock)->toBe(7)
            ->and($marking->fresh()->stock)->toBe(1);
    });

    it('cannot change the catalogue or cancel orders', function (string $method, string $name) {
        [$product, $variant, $marking] = sellable();
        $params = [
            'article' => $product->article, 'variant' => $variant, 'marking' => $marking,
            'product' => $product, 'order' => Order::factory()->create(),
        ];
        $route = Route::getRoutes()->getByName($name);
        $needed = array_intersect_key($params, array_flip($route?->parameterNames() ?? []));

        $this->call($method, route($name, $needed), [
            'name' => 'X', 'category_id' => $product->article->category_id, 'description' => 'x', 'silhouette' => 'tshirt',
            'technique' => 'flocking', 'ink_color' => 'Noir', 'ink_hex' => '#000000', 'stock' => 1,
            'article_id' => $product->article_id, 'marking_id' => Marking::factory()->create()->id,
            'price' => '10', 'units_per_item' => 1,
            'color_name' => 'Rouge', 'color_hex' => '#FF0000', 'size' => 'M', 'sku' => 'NEW-1',
        ])->assertForbidden();
    })->with([
        ['GET', 'admin.articles.create'],
        ['POST', 'admin.articles.store'],
        ['PUT', 'admin.articles.update'],
        ['DELETE', 'admin.articles.destroy'],
        ['POST', 'admin.articles.variants.store'],
        ['DELETE', 'admin.articles.variants.destroy'],
        ['GET', 'admin.markings.create'],
        ['POST', 'admin.markings.store'],
        ['PUT', 'admin.markings.update'],
        ['GET', 'admin.products.create'],
        ['POST', 'admin.products.store'],
        ['GET', 'admin.products.edit'],
        ['PUT', 'admin.products.update'],
        ['POST', 'admin.orders.cancel'],
    ]);

    it('sees the catalogue forms read-only', function () {
        [$product] = sellable();

        $this->get(route('admin.articles.edit', $product->article))
            ->assertOk()
            ->assertSee('Lecture seule')
            ->assertDontSee('Ajouter une variante');
    });
});

describe('admin', function () {
    beforeEach(fn () => $this->actingAs($this->admin = User::factory()->admin()->create()));

    it('creates an article and a variant', function () {
        $category = App\Models\Category::factory()->create();

        $this->post(route('admin.articles.store'), [
            'name' => 'Polo Piqué', 'category_id' => $category->id, 'description' => 'Col boutonné.',
            'silhouette' => 'tshirt', 'is_active' => '1',
        ])->assertRedirect();

        $article = Article::where('slug', 'polo-pique')->sole();
        $this->post(route('admin.articles.variants.store', $article), [
            'color_name' => 'Marine', 'color_hex' => '#1F2A44', 'size' => 'L', 'sku' => 'POLO-MARINE-L', 'stock' => 12,
        ])->assertSessionHasNoErrors();

        expect($article->variants()->sole()->stock)->toBe(12);
    });

    it('refuses a duplicate colour and size on the same article', function () {
        $variant = ArticleVariant::factory()->create(['color_name' => 'Noir', 'size' => 'M']);

        $this->post(route('admin.articles.variants.store', $variant->article), [
            'color_name' => 'Noir', 'color_hex' => '#000000', 'size' => 'M', 'sku' => 'OTHER', 'stock' => 1,
        ])->assertSessionHasErrors('size');
    });

    it('creates a marking with its initial stock, then never edits the stock directly', function () {
        $this->post(route('admin.markings.store'), [
            'name' => 'Vive le lundi', 'technique' => 'screen_printing', 'ink_color' => 'Noir', 'ink_hex' => '#000000',
            'stock' => 40, 'is_unlimited' => '0', 'is_active' => '1',
        ])->assertRedirect();

        $marking = Marking::where('slug', 'vive-le-lundi')->sole();
        expect($marking->stock)->toBe(40);

        $this->put(route('admin.markings.update', $marking), [
            'name' => 'Vive le lundi', 'technique' => 'screen_printing', 'ink_color' => 'Noir', 'ink_hex' => '#000000',
            'stock' => 999,
        ])->assertSessionHasErrors('stock');
        expect($marking->fresh()->stock)->toBe(40);
    });

    it('offers a marking on an article, priced in francs and stored in cents', function () {
        $article = Article::factory()->create();
        $marking = Marking::factory()->create();

        $this->post(route('admin.products.store'), [
            'article_id' => $article->id, 'marking_id' => $marking->id, 'price' => '34.90', 'units_per_item' => 2, 'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::sole();
        expect($product->price_cents)->toBe(3490)->and($product->units_per_item)->toBe(2);

        $this->post(route('admin.products.store'), [
            'article_id' => $article->id, 'marking_id' => $marking->id, 'price' => '30', 'units_per_item' => 1,
        ])->assertSessionHasErrors('marking_id');
    });

    it('cancels an order and restocks', function () {
        Illuminate\Support\Facades\Queue::fake();
        [$product, $variant] = sellable(variantStock: 3);
        $order = app(App\Actions\Orders\PlaceOrder::class)->handle([line($product, $variant, 2)], customer());

        $this->post(route('admin.orders.cancel', $order))->assertSessionHas('status');
        $this->post(route('admin.orders.cancel', $order))->assertSessionHas('status', 'Cette commande était déjà annulée.');

        expect($variant->fresh()->stock)->toBe(3)
            ->and($order->fresh()->cancelled_by)->toBe($this->admin->id);
    });

    it('refuses to delete an article that has products', function () {
        [$product] = sellable();

        $this->delete(route('admin.articles.destroy', $product->article))->assertSessionHas('error');
        expect($product->article->fresh())->not->toBeNull();
    });

    it('reports an adjustment that would make the stock negative', function () {
        $variant = ArticleVariant::factory()->stock(1)->create();

        $this->post(route('admin.stock.variants.adjust', $variant), ['delta' => -5])
            ->assertSessionHasErrors(['delta' => 'Stock actuel 1 : impossible de retirer 5.']);
    });

    it('validates the adjustment', function (mixed $delta) {
        $variant = ArticleVariant::factory()->create();

        $this->post(route('admin.stock.variants.adjust', $variant), ['delta' => $delta])->assertSessionHasErrors('delta');
    })->with([0, '', 'dix', 1.5]);
});
