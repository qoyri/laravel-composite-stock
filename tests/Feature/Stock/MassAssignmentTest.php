<?php

declare(strict_types=1);

use App\Models\Product;
use Illuminate\Database\Eloquent\MassAssignmentException;

/*
 * A Pivot model ships with $guarded = [] — fully open to mass assignment.
 * Product is a Pivot, so only its #[Fillable] attribute keeps request data
 * from writing columns nobody meant to expose.
 */

it('does not let mass assignment reach columns outside #[Fillable]', function () {
    expect((new Product)->getGuarded())->toBe([])          // the Pivot default
        ->and((new Product)->isFillable('id'))->toBeFalse()
        ->and((new Product)->isFillable('created_at'))->toBeFalse()
        ->and((new Product)->isFillable('price_cents'))->toBeTrue();
});

it('refuses silently dropping an unexpected attribute', function () {
    // Model::shouldBeStrict() turns a discarded attribute into an exception
    // instead of a silent no-op (AppServiceProvider, outside production).
    [$product] = sellable();

    $product->fill(['id' => 999, 'price_cents' => 4200]);
})->throws(MassAssignmentException::class);
