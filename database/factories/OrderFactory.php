<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Bare order rows, without lines or stock effects. Tests and seeders that
 * need a real sale go through App\Actions\Orders\PlaceOrder instead.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(20, 200) * 100;
        $shipping = $subtotal >= 5000 ? 0 : 500;

        return [
            'reference' => 'AC-'.Str::upper(Str::random(8)),
            'status' => OrderStatus::Confirmed,
            'email' => fake()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => null,
            'address_line' => fake()->streetAddress(),
            'postal_code' => (string) fake()->numberBetween(1000, 9658),
            'city' => fake()->city(),
            'country' => 'CH',
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $subtotal + $shipping,
        ];
    }
}
