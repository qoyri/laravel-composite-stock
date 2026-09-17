<?php

declare(strict_types=1);

use App\Cart\Cart;
use App\Models\ArticleVariant;

function addToCart($test, $product, $variant, int $quantity = 1)
{
    return $test->from(route('products.show', $product->slug))->post(route('cart.store'), [
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);
}

it('adds a line and opens the drawer on the next page', function () {
    [$product, $variant] = sellable();

    addToCart($this, $product, $variant, 2)
        ->assertRedirect(route('products.show', $product->slug))
        ->assertSessionHas('cart.opened', true);

    expect(app(Cart::class)->quantityOf($product->id, $variant->id))->toBe(2);
});

it('merges repeated additions of the same product and variant', function () {
    [$product, $variant] = sellable();

    addToCart($this, $product, $variant, 1);
    addToCart($this, $product, $variant, 2);

    expect(app(Cart::class)->lines())->toHaveCount(1)
        ->and(app(Cart::class)->quantityOf($product->id, $variant->id))->toBe(3);
});

it('refuses a variant of another article', function () {
    [$product] = sellable();
    $foreign = ArticleVariant::factory()->create();

    addToCart($this, $product, $foreign)->assertSessionHasErrors('variant_id');
    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('refuses more than the components allow, counting what is already in the cart', function () {
    [$product, $variant] = sellable(variantStock: 10, markingStock: 3);

    addToCart($this, $product, $variant, 2)->assertSessionHasNoErrors();
    addToCart($this, $product, $variant, 2)
        ->assertSessionHasErrors(['quantity' => 'Plus que 3 disponible(s) dans cette taille.']);

    expect(app(Cart::class)->quantityOf($product->id, $variant->id))->toBe(2);
});

it('refuses a sold-out size', function () {
    [$product, $variant] = sellable(variantStock: 0);

    addToCart($this, $product, $variant)->assertSessionHasErrors(['quantity' => 'Cette taille est épuisée.']);
});

it('refuses an inactive product', function () {
    [$product, $variant] = sellable();
    $product->update(['is_active' => false]);

    addToCart($this, $product, $variant)->assertSessionHasErrors('product_id');
});

it('validates the quantity', function (mixed $quantity) {
    [$product, $variant] = sellable();

    addToCart($this, $product, $variant, 1);
    $this->post(route('cart.store'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => $quantity])
        ->assertSessionHasErrors('quantity');
})->with([0, -1, 21, 'deux']);

it('updates, removes and clears lines', function () {
    [$product, $variant] = sellable();
    [$other, $otherVariant] = sellable();
    addToCart($this, $product, $variant, 1);
    addToCart($this, $other, $otherVariant, 1);
    $key = $product->id.':'.$variant->id;

    $this->patch(route('cart.update', $key), ['quantity' => 4])->assertRedirect();
    expect(app(Cart::class)->quantityOf($product->id, $variant->id))->toBe(4);

    $this->patch(route('cart.update', $key), ['quantity' => 0]);
    expect(app(Cart::class)->find($key))->toBeNull();

    $this->delete(route('cart.destroy', $other->id.':'.$otherVariant->id));
    expect(app(Cart::class)->isEmpty())->toBeTrue();

    addToCart($this, $product, $variant, 1);
    $this->delete(route('cart.clear'))->assertRedirect(route('cart.index'));
    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('shows the cart with shipping and flags lines that can no longer be ordered', function () {
    [$product, $variant] = sellable(variantStock: 5, priceCents: 2000);
    addToCart($this, $product, $variant, 2);

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertSee('40.– CHF')
        ->assertSee('5.– CHF')                               // shipping under 50.–
        ->assertSee('Ajoutez 10.– CHF pour la livraison offerte');

    $variant->update(['stock' => 1]);

    $this->get(route('cart.index'))
        ->assertSee('Plus que 1 disponible(s) : réduisez la quantité.')
        ->assertSee('Corrigez les lignes signalées pour commander.');
});
