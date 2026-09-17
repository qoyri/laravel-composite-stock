<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\Marking;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'marking_id' => Marking::factory(),
            'slug' => 'produit-'.Str::lower(Str::random(12)),
            'price_cents' => 3500,
            'units_per_item' => 1,
            'is_active' => true,
        ];
    }

    public function price(int $cents): static
    {
        return $this->state(fn (array $attributes) => ['price_cents' => $cents]);
    }

    public function unitsPerItem(int $units): static
    {
        return $this->state(fn (array $attributes) => ['units_per_item' => $units]);
    }
}
