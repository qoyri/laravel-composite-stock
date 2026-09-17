<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Size;
use App\Models\Article;
use App\Models\ArticleVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArticleVariant>
 */
class ArticleVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'color_name' => fake()->unique()->safeColorName(),
            'color_hex' => fake()->hexColor(),
            'size' => fake()->randomElement(Size::adult()),
            'sku' => 'SKU-'.Str::upper(Str::random(10)),
            'stock' => 20,
        ];
    }

    public function stock(int $stock): static
    {
        return $this->state(fn (array $attributes) => ['stock' => $stock]);
    }
}
