<?php

declare(strict_types=1);

use App\Cart\Cart;
use App\Jobs\SendOrderConfirmation;
use App\Models\Order;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

beforeEach(fn () => Queue::fake());

function checkoutForm(array $overrides = []): array
{
    return [
        'email' => 'Lea.Muller@Example.ch',
        'first_name' => 'Léa',
        'last_name' => 'Müller',
        'phone' => '+41 22 123 45 67',
        'address_line' => 'Rue du Marché 12',
        'postal_code' => '1204',
        'city' => 'Genève',
        ...$overrides,
    ];
}

function fillCart($product, $variant, int $quantity = 1): void
{
    app(Cart::class)->add(line($product, $variant, $quantity));
}

it('redirects an empty cart away from the checkout', function () {
    $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
    $this->post(route('checkout.store'), checkoutForm())->assertRedirect(route('cart.index'));
    expect(Order::count())->toBe(0);
});

it('shows the checkout form with the summary', function () {
    [$product, $variant] = sellable(priceCents: 3000);
    fillCart($product, $variant, 2);

    $this->get(route('checkout.create'))
        ->assertOk()
        ->assertSee('Adresse de livraison')
        ->assertSee('60.– CHF')
        ->assertSee('Offerte');
});

it('places the order, empties the cart and redirects to a signed confirmation', function () {
    [$product, $variant, $marking] = sellable(variantStock: 5, markingStock: 5);
    fillCart($product, $variant, 2);

    $response = $this->post(route('checkout.store'), checkoutForm());

    $order = Order::sole();
    expect($order->email)->toBe('lea.muller@example.ch')
        ->and($variant->fresh()->stock)->toBe(3)
        ->and($marking->fresh()->stock)->toBe(3)
        ->and(app(Cart::class)->isEmpty())->toBeTrue();

    $response->assertRedirectContains('/commande/'.$order->id.'/confirmation?expires=');
    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee($order->reference);

    Queue::assertPushed(SendOrderConfirmation::class);
});

it('refuses the order cleanly when a component ran out after the cart was filled', function () {
    [$product, $variant, $marking] = sellable(variantStock: 5, markingStock: 2);
    fillCart($product, $variant, 2);
    $marking->update(['stock' => 1]);     // consumed by another sale meanwhile

    // Not chained with assertSessionHasErrors(): in tests it drops the flashed
    // bag before the next request, and the page below is the real check.
    $this->post(route('checkout.store'), checkoutForm())
        ->assertRedirect(route('cart.index'));

    expect(Order::count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(5)
        ->and(app(Cart::class)->quantityOf($product->id, $variant->id))->toBe(2);
    Queue::assertNothingPushed();

    $this->get(route('cart.index'))
        ->assertSee("Le stock a changé depuis l'ajout au panier", false)
        ->assertSee('2 nécessaire(s), 1 en stock');
});

it('validates the customer details', function (array $override, string $field) {
    [$product, $variant] = sellable();
    fillCart($product, $variant);

    $this->post(route('checkout.store'), checkoutForm($override))->assertSessionHasErrors($field);
    expect(Order::count())->toBe(0);
})->with([
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'pas-un-email'], 'email'],
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing address' => [['address_line' => ''], 'address_line'],
    'French postcode' => [['postal_code' => '75015'], 'postal_code'],
    'postcode with letters' => [['postal_code' => '12AB'], 'postal_code'],
    'phone with letters' => [['phone' => 'appelez-moi'], 'phone'],
]);

it('refuses a confirmation URL without a valid signature', function () {
    [$mine, $someoneElses] = Order::factory()->count(2)->create();
    $signed = URL::temporarySignedRoute('orders.confirmation', now()->addDay(), $mine);

    $this->get($signed)->assertOk();
    $this->get(route('orders.confirmation', $mine))->assertForbidden();
    // Same signature, another order id: guessing ids gets nowhere.
    $this->get(str_replace('/commande/'.$mine->id.'/', '/commande/'.$someoneElses->id.'/', $signed))->assertForbidden();
    $this->get(URL::temporarySignedRoute('orders.confirmation', now()->subMinute(), $mine))->assertForbidden();
});
