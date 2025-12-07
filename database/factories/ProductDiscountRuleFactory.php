<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuoteDiscountType;
use App\Models\Product;
use App\Models\ProductDiscountRule;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDiscountRule>
 */
final class ProductDiscountRuleFactory extends Factory
{
    protected $model = ProductDiscountRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'team_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->team_id ?? Team::factory(),
            'name' => 'Rule '.fake()->unique()->numberBetween(1, 9999),
            'scope' => 'product',
            'discount_type' => QuoteDiscountType::PERCENT,
            'discount_value' => fake()->numberBetween(5, 20),
            'min_quantity' => 1,
            'max_quantity' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'is_active' => true,
            'priority' => 0,
        ];
    }
}
