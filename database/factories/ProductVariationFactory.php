<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariation>
 */
final class ProductVariationFactory extends Factory
{
    protected $model = ProductVariation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(2, true),
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}-VAR'),
            'price' => fake()->randomFloat(2, 10, 1000),
            'currency_code' => 'USD',
            'is_default' => false,
            'track_inventory' => fake()->boolean(),
            'inventory_quantity' => fake()->numberBetween(0, 100),
            'options' => [],
        ];
    }
}
