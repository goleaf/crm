<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class ProductDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $team = Team::query()->first();

        if (! $team) {
            $this->command?->warn('Skipping ProductDemoSeeder: no team found.');

            return;
        }

        // Categories
        $software = ProductCategory::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'software'],
            ['name' => 'Software', 'description' => 'Software and subscriptions']
        );

        $hardware = ProductCategory::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'hardware'],
            ['name' => 'Hardware', 'description' => 'Devices and accessories']
        );

        $headsets = ProductCategory::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'headsets'],
            ['name' => 'Headsets', 'description' => 'Audio devices', 'parent_id' => $hardware->id]
        );

        // Attributes & values
        $color = ProductAttribute::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'color'],
            [
                'name' => 'Color',
                'data_type' => 'select',
                'is_configurable' => true,
                'is_filterable' => true,
                'is_required' => true,
            ]
        );

        $sizes = ProductAttribute::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'size'],
            [
                'name' => 'Size',
                'data_type' => 'select',
                'is_configurable' => true,
                'is_filterable' => true,
            ]
        );

        $material = ProductAttribute::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'material'],
            [
                'name' => 'Material',
                'data_type' => 'text',
                'is_configurable' => false,
                'is_filterable' => true,
            ]
        );

        $colors = collect(['Black', 'White', 'Silver'])
            ->mapWithKeys(fn (string $value): array => [
                strtolower($value) => ProductAttributeValue::query()->firstOrCreate(
                    ['product_attribute_id' => $color->id, 'value' => $value],
                    ['code' => strtolower($value), 'sort_order' => 0]
                )->id,
            ]);

        $sizesValues = collect(['Small', 'Medium', 'Large'])
            ->mapWithKeys(fn (string $value): array => [
                strtolower($value) => ProductAttributeValue::query()->firstOrCreate(
                    ['product_attribute_id' => $sizes->id, 'value' => $value],
                    ['code' => substr($value, 0, 1), 'sort_order' => 0]
                )->id,
            ]);

        // Product: SaaS subscription (no variants)
        $crm = Product::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'relaticle-crm-pro'],
            [
                'name' => 'Relaticle CRM Pro',
                'sku' => 'CRM-PRO',
                'price' => 199,
                'description' => 'All-in-one CRM subscription with pipelines, knowledge base, and automations.',
                'is_active' => true,
                'track_inventory' => false,
                'custom_fields' => ['edition' => 'Pro', 'term' => 'monthly'],
            ]
        );

        $crm->categories()->sync([$software->id]);
        $crm->configurableAttributes()->sync([$material->id]);
        $crm->attributeAssignments()->updateOrCreate(
            ['product_attribute_id' => $material->id],
            ['custom_value' => 'Cloud service']
        );

        // Product: Hardware with variants and inventory
        $headset = Product::query()->firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'nova-wireless-headset'],
            [
                'name' => 'Nova Wireless Headset',
                'sku' => 'NV-HS-100',
                'price' => 129,
                'description' => 'Wireless ANC headset with dual microphones and fast charging.',
                'is_active' => true,
                'track_inventory' => true,
                'inventory_quantity' => 150,
                'custom_fields' => ['warranty' => '2y'],
            ]
        );

        $headset->categories()->sync([$hardware->id, $headsets->id]);
        $headset->configurableAttributes()->sync([$color->id, $sizes->id]);

        $headset->variations()->updateOrCreate(
            ['sku' => 'NV-HS-100-BLK-S'],
            [
                'name' => 'Black / Small',
                'price' => 129,
                'currency_code' => 'USD',
                'track_inventory' => true,
                'inventory_quantity' => 40,
                'options' => [
                    ['attribute_id' => $color->id, 'value_id' => $colors['black']],
                    ['attribute_id' => $sizes->id, 'value_id' => $sizesValues['small']],
                ],
            ]
        );

        $headset->variations()->updateOrCreate(
            ['sku' => 'NV-HS-100-BLK-M'],
            [
                'name' => 'Black / Medium',
                'price' => 129,
                'currency_code' => 'USD',
                'track_inventory' => true,
                'inventory_quantity' => 60,
                'options' => [
                    ['attribute_id' => $color->id, 'value_id' => $colors['black']],
                    ['attribute_id' => $sizes->id, 'value_id' => $sizesValues['medium']],
                ],
            ]
        );

        $headset->variations()->updateOrCreate(
            ['sku' => 'NV-HS-100-SLV-L'],
            [
                'name' => 'Silver / Large',
                'price' => 139,
                'currency_code' => 'USD',
                'track_inventory' => true,
                'inventory_quantity' => 50,
                'options' => [
                    ['attribute_id' => $color->id, 'value_id' => $colors['silver']],
                    ['attribute_id' => $sizes->id, 'value_id' => $sizesValues['large']],
                ],
            ]
        );
    }
}
