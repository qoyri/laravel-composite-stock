<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->word()).' '.fake()->unique()->numberBetween(1, 9_999_999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => 'Explorer la collection',
            'position' => 0,
        ];
    }
}
