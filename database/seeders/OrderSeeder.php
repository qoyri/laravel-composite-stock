<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Cart\CartLine;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Orders\CustomerDetails;
use App\Stock\InsufficientStock;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Order history created through the real PlaceOrder / CancelOrder actions,
 * so stock levels and the movement log stay consistent with the orders.
 */
class OrderSeeder extends Seeder
{
    private const ORDERS = 40;

    private const CANCELLED = 5;

    public function __construct(
        private readonly PlaceOrder $placeOrder,
        private readonly CancelOrder $cancelOrder,
    ) {}

    public function run(): void
    {
        $faker = FakerFactory::create('fr_CH');
        $faker->seed(1291);
        mt_srand(1291);

        $products = Product::query()->sellable()->with('article.variants')->get();
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $placed = [];
        // Fixed reference: now() inside the loop would return the previous
        // fake time and push every order further into the past.
        $today = now();

        for ($i = 0; $i < self::ORDERS; $i++) {
            Carbon::setTestNow($today->copy()->subDays(mt_rand(0, 45))->subMinutes(mt_rand(0, 1440)));

            $lines = [];
            foreach (range(1, mt_rand(1, 3)) as $_) {
                /** @var Product $product */
                $product = $products->random();
                // Customers mostly pick what is shown as available; a few pick a sold-out size.
                $candidates = mt_rand(1, 10) === 1
                    ? $product->article->variants
                    : $product->article->variants->where('stock', '>', 0);
                $variant = ($candidates->isEmpty() ? $product->article->variants : $candidates)->random();
                $lines[] = new CartLine($product->id, $variant->id, mt_rand(1, 10) === 1 ? 2 : 1);
            }

            try {
                $placed[] = $this->placeOrder->handle($this->uniqueLines($lines), new CustomerDetails(
                    email: $faker->unique()->safeEmail(),
                    firstName: $faker->firstName(),
                    lastName: $faker->lastName(),
                    phone: mt_rand(0, 1) === 1 ? $faker->phoneNumber() : null,
                    addressLine: $faker->streetAddress(),
                    postalCode: (string) $faker->numberBetween(1000, 9658),
                    city: $faker->city(),
                ));
            } catch (InsufficientStock) {
                // A sold-out combination was drawn: exactly what a customer would hit.
            }
        }

        foreach (array_slice($placed, 0, self::CANCELLED) as $order) {
            Carbon::setTestNow($order->created_at?->copy()->addHours(3));
            $this->cancelOrder->handle($order, $admin);
        }

        Carbon::setTestNow();
        mt_srand();

        // Demo data: don't queue three dozen confirmation emails for fake customers.
        DB::table('jobs')->where('payload', 'like', '%SendOrderConfirmation%')->delete();
    }

    /**
     * Merges lines that drew the same product and variant.
     *
     * @param  list<CartLine>  $lines
     * @return list<CartLine>
     */
    private function uniqueLines(array $lines): array
    {
        $merged = [];
        foreach ($lines as $line) {
            $existing = $merged[$line->key()] ?? null;
            $merged[$line->key()] = $existing === null ? $line : $existing->withQuantity($existing->quantity + $line->quantity);
        }

        return array_values($merged);
    }
}
