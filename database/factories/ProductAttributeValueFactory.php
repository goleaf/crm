<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeValue>
 */
final class ProductAttributeValueFactory extends Factory
{
    protected $model = ProductAttributeValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_attribute_id' => ProductAttribute::factory(),
            'value' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
