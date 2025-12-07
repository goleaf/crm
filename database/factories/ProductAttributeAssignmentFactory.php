<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeAssignment>
 */
final class ProductAttributeAssignmentFactory extends Factory
{
    protected $model = ProductAttributeAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_attribute_id' => ProductAttribute::factory(),
            'product_attribute_value_id' => ProductAttributeValue::factory(),
            'custom_value' => null,
        ];
    }
}
