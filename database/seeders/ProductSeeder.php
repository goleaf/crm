<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating products (1500) with categories, attributes, and variations...');

        $teams = Team::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found. Run UserTeamSeeder first.');

            return;
        }

        // Create categories
        $categories = [];
        for ($i = 0; $i < 50; $i++) {
            $name = fake()->words(2, true);
            $categories[] = [
                'team_id' => $teams->random()->id,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
                'description' => fake()->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ProductCategory::insert($categories);
        ProductCategory::pluck('id');

        // Create attributes
        $attributes = [];
        $attributeNames = ['Color', 'Size', 'Material', 'Weight', 'Dimensions', 'Brand', 'Model', 'Warranty'];
        foreach ($attributeNames as $name) {
            foreach ($teams->random(10) as $team) {
                $attributes[] = [
                    'team_id' => $team->id,
                    'name' => $name,
                    'slug' => strtolower($name).'-'.fake()->unique()->numberBetween(1, 10000),
                    'data_type' => fake()->randomElement(['text', 'select', 'number']),
                    'is_configurable' => fake()->boolean(40),
                    'is_filterable' => fake()->boolean(60),
                    'is_required' => fake()->boolean(30),
                    'description' => fake()->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        ProductAttribute::insert($attributes);
        $attributeIds = ProductAttribute::pluck('id');

        // Create products
        $products = [];
        for ($i = 0; $i < 1500; $i++) {
            $name = fake()->words(3, true);
            $sku = fake()->unique()->bothify('SKU-####-????');
            $products[] = [
                'team_id' => $teams->random()->id,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name).'-'.$i,
                'sku' => $sku,
                'description' => fake()->paragraph(),
                'price' => fake()->randomFloat(2, 10, 10000),
                'currency_code' => fake()->randomElement(['USD', 'EUR', 'GBP']),
                'is_active' => fake()->boolean(70),
                'track_inventory' => fake()->boolean(50),
                'inventory_quantity' => fake()->numberBetween(1, 50),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($products, 500) as $chunk) {
            Product::insert($chunk);
        }

        $productIds = Product::pluck('id');
        $categoryIds = ProductCategory::pluck('id');

        // Attach categories to products
        $categoryAttachments = [];
        foreach ($productIds as $productId) {
            $selectedCategories = $categoryIds->random(random_int(1, 3));
            foreach ($selectedCategories as $categoryId) {
                $categoryAttachments[] = [
                    'product_id' => $productId,
                    'product_category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($categoryAttachments, 500) as $chunk) {
            \Illuminate\Support\Facades\DB::table('category_product')->insert($chunk);
        }

        // Create attribute values
        $values = [];
        foreach ($attributeIds as $attributeId) {
            for ($i = 0; $i < random_int(3, 8); $i++) {
                $values[] = [
                    'product_attribute_id' => $attributeId,
                    'value' => fake()->word(),
                    'code' => fake()->optional()->bothify('??##'),
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($values, 500) as $chunk) {
            ProductAttributeValue::insert($chunk);
        }

        $valueIds = ProductAttributeValue::pluck('id');

        // Create attribute assignments
        $assignments = [];
        foreach ($productIds->random(1000) as $productId) {
            $selectedAttributes = $attributeIds->random(random_int(2, 5));

            foreach ($selectedAttributes as $attributeId) {
                $assignments[] = [
                    'product_id' => $productId,
                    'product_attribute_id' => $attributeId,
                    'product_attribute_value_id' => $valueIds->random(),
                    'custom_value' => fake()->optional()->word(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($assignments, 500) as $chunk) {
            ProductAttributeAssignment::insert($chunk);
        }

        // Create variations
        $variations = [];
        foreach ($productIds->random(300) as $productId) {
            for ($i = 0; $i < random_int(2, 5); $i++) {
                $variations[] = [
                    'product_id' => $productId,
                    'sku' => fake()->unique()->bothify('VAR-####-????'),
                    'name' => fake()->words(2, true),
                    'price' => fake()->randomFloat(2, 10, 10000),
                    'currency_code' => fake()->randomElement(['USD', 'EUR', 'GBP']),
                    'is_default' => $i === 0,
                    'track_inventory' => fake()->boolean(70),
                    'inventory_quantity' => fake()->numberBetween(0, 500),
                    'options' => json_encode(['size' => fake()->word(), 'color' => fake()->colorName()]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($variations, 500) as $chunk) {
            ProductVariation::insert($chunk);
        }

        $this->command->info('✓ Created 1500 products with categories, attributes, and variations');
    }
}
