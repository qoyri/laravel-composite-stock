<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Silhouette;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $name = 'T-shirt '.fake()->word().' '.fake()->unique()->numberBetween(1, 9_999_999);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'material' => 'Coton bio 180 g/m²',
            'silhouette' => Silhouette::TShirt,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
