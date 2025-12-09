<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductAttribute;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
final class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'team_id' => Team::factory(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'data_type' => fake()->randomElement(['string', 'number', 'boolean']),
            'is_configurable' => fake()->boolean(),
            'is_filterable' => fake()->boolean(),
            'is_required' => false,
            'description' => fake()->sentence(),
        ];
    }
}
