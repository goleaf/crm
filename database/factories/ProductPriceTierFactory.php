<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPriceTier>
 */
final class ProductPriceTierFactory extends Factory
{
    protected $model = ProductPriceTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'team_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->team_id ?? Team::factory(),
            'min_quantity' => 1,
            'max_quantity' => null,
            'price' => fake()->randomFloat(2, 5, 500),
            'currency_code' => 'USD',
            'starts_at' => now(),
            'ends_at' => null,
            'label' => fake()->words(2, true),
        ];
    }
}
