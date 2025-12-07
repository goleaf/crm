<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'team_id' => Team::factory(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'part_number' => fake()->unique()->bothify('PART-####'),
            'manufacturer' => fake()->company(),
            'product_type' => fake()->randomElement(['stocked', 'service', 'non_stock']),
            'status' => 'active',
            'lifecycle_stage' => fake()->randomElement(['draft', 'released']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'cost_price' => fake()->randomFloat(2, 5, 500),
            'currency_code' => 'USD',
            'is_active' => true,
            'is_bundle' => fake()->boolean(10),
            'track_inventory' => fake()->boolean(),
            'inventory_quantity' => fake()->numberBetween(0, 100),
            'price_effective_from' => null,
            'price_effective_to' => null,
        ];
    }
}
