<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRelationship;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRelationship>
 */
final class ProductRelationshipFactory extends Factory
{
    protected $model = ProductRelationship::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'related_product_id' => Product::factory(),
            'team_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->team_id ?? Team::factory(),
            'relationship_type' => fake()->randomElement(['cross_sell', 'upsell', 'bundle']),
            'priority' => fake()->numberBetween(0, 5),
            'quantity' => fake()->numberBetween(1, 3),
            'price_override' => fake()->optional()->randomFloat(2, 10, 500),
            'is_required' => fake()->boolean(10),
        ];
    }
}
