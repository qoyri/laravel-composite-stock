<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Marking;
use App\Models\Product;
use Database\Seeders\CatalogueSeeder;

it('renders every storefront page on the seeded catalogue', function (string $uri) {
    $this->seed(CatalogueSeeder::class);

    $this->get($uri)->assertOk();
})->with([
    'home' => '/',
    'catalogue' => '/produits',
    'category' => '/produits?categorie=homme',
    'technique + available + sort' => '/produits?technique=embroidery&disponible=1&tri=prix-croissant',
    'search' => '/produits?q=raclette',
    'page 2' => '/produits?page=2',
    'markings' => '/personnaliser',
    'about' => '/le-magasin',
    'empty cart' => '/panier',
]);

it('shows a product with its availability matrix and both sides of the many-to-many', function () {
    $this->seed(CatalogueSeeder::class);
    $product = Product::where('slug', 'jhabite-chez-mon-chat-t-shirt-classique')->firstOrFail();

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('J&#039;habite chez mon chat', false)
        ->assertSee('Ce vêtement existe aussi avec')
        ->assertSee('existe aussi sur')
        ->assertSee('\u0022available\u0022', false);   // the JSON handed to Alpine by @js()
});

it('hides products that cannot be sold', function (string $what) {
    [$product] = sellable();
    match ($what) {
        'product' => $product->update(['is_active' => false]),
        'article' => $product->article->update(['is_active' => false]),
        'marking' => $product->marking->update(['is_active' => false]),
    };

    $this->get(route('products.show', $product->slug))->assertNotFound();
    $this->get(route('catalog'))->assertOk()->assertDontSee($product->marking->name);
})->with(['product', 'article', 'marking']);

it('filters the catalogue by category and search term', function () {
    $homme = Category::factory()->create(['slug' => 'homme', 'name' => 'Homme']);
    $femme = Category::factory()->create(['slug' => 'femme', 'name' => 'Femme']);
    $raclette = Marking::factory()->create(['name' => 'Je peux pas, j\'ai raclette']);
    $chat = Marking::factory()->create(['name' => 'Chat perché']);
    Product::factory()->for(Article::factory()->for($homme))->for($raclette)->create();
    Product::factory()->for(Article::factory()->for($femme))->for($chat)->create();

    $this->get('/produits?categorie=homme')->assertSee('raclette')->assertDontSee('Chat perché');
    $this->get('/produits?q=perch')->assertSee('Chat perché')->assertDontSee('raclette');
});

it('rejects invalid catalogue parameters', function () {
    $this->get('/produits?tri=nimportequoi')->assertSessionHasErrors('tri');
    $this->get('/produits?technique=tatouage')->assertSessionHasErrors('technique');
});

it('lists only products with something to sell when asked', function () {
    [$inStock] = sellable(variantStock: 3, markingStock: 3);
    [$noTextile] = sellable(variantStock: 0, markingStock: 3);
    [$noMarking] = sellable(variantStock: 3, markingStock: 0);
    [$tooFewUnits] = sellable(variantStock: 3, markingStock: 1, unitsPerItem: 2);

    $this->get('/produits?disponible=1')
        ->assertSee($inStock->marking->name)
        ->assertDontSee($noTextile->marking->name)
        ->assertDontSee($noMarking->marking->name)
        ->assertDontSee($tooFewUnits->marking->name);
});
