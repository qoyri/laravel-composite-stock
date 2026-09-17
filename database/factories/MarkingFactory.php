<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarkingTechnique;
use App\Models\Marking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Marking>
 */
class MarkingFactory extends Factory
{
    public function definition(): array
    {
        $name = rtrim(fake()->sentence(3), '.').' '.fake()->unique()->numberBetween(1, 9_999_999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'technique' => MarkingTechnique::ScreenPrinting,
            'ink_color' => 'Blanc',
            'ink_hex' => '#FFFFFF',
            'is_unlimited' => false,
            'stock' => 50,
            'is_active' => true,
        ];
    }

    public function stock(int $stock): static
    {
        return $this->state(fn (array $attributes) => ['stock' => $stock, 'is_unlimited' => false]);
    }

    /** Print-on-demand: no consumable to run out of. */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'technique' => MarkingTechnique::Flocking,
            'is_unlimited' => true,
            'stock' => 0,
        ]);
    }
}
