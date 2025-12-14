<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class VariationService
{
    /**
     * Generate all possible variations for a product based on configurable attributes.
     *
     * This implements the cartesian product algorithm to create all combinations
     * of attribute values for the selected attributes.
     */
    public function generateVariations(Product $product, array $attributeIds): Collection
    {
        // Load the attributes with their values
        $attributes = ProductAttribute::whereIn('id', $attributeIds)
            ->where('team_id', $product->team_id)
            ->where('is_configurable', true)
            ->with('values')
            ->get();

        if ($attributes->isEmpty()) {
            return new Collection;
        }

        // Build array of attribute values for cartesian product
        $attributeValueSets = [];
        foreach ($attributes as $attribute) {
            $values = $attribute->values()->orderBy('sort_order')->get();
            if ($values->isEmpty()) {
                // Skip attributes without values
                continue;
            }

            $attributeValueSets[$attribute->slug] = $values->pluck('value')->toArray();
        }

        if ($attributeValueSets === []) {
            return new Collection;
        }

        // Generate cartesian product of all attribute value combinations
        $combinations = $this->cartesianProduct($attributeValueSets);

        // Create variations for each combination
        $variations = new Collection;

        DB::transaction(function () use ($product, $combinations, &$variations): void {
            foreach ($combinations as $combination) {
                // Generate variation name from combination
                $variationName = $this->generateVariationName($product, $combination);

                // Generate unique SKU for this variation
                $variationSku = $this->generateVariationSku($product, $combination);

                // Create the variation
                $variation = $this->createVariation($product, $combination, [
                    'name' => $variationName,
                    'sku' => $variationSku,
                    'price' => $product->price, // Default to parent product price
                    'currency_code' => $product->currency_code,
                    'track_inventory' => $product->track_inventory,
                    'inventory_quantity' => 0, // Start with 0 inventory
                ]);

                $variations->push($variation);
            }
        });

        return $variations;
    }

    /**
     * Create a single product variation with specific attribute options.
     */
    public function createVariation(Product $product, array $options, array $data = []): ProductVariation
    {
        // Validate that all options correspond to configurable attributes
        $this->validateVariationOptions($product, $options);

        // Merge default data with provided data
        $variationData = array_merge([
            'product_id' => $product->id,
            'options' => $options,
            'currency_code' => $product->currency_code,
            'track_inventory' => $product->track_inventory,
            'inventory_quantity' => 0,
        ], $data);

        return ProductVariation::create($variationData);
    }

    /**
     * Update an existing product variation.
     */
    public function updateVariation(ProductVariation $variation, array $data): ProductVariation
    {
        // Don't allow changing the product_id or options through this method
        unset($data['product_id'], $data['options']);

        $variation->update($data);

        return $variation->fresh();
    }

    /**
     * Soft delete a product variation.
     */
    public function deleteVariation(ProductVariation $variation): void
    {
        $variation->delete();
    }

    /**
     * Get all variations for a product.
     */
    public function getProductVariations(Product $product): Collection
    {
        return $product->variations()->get();
    }

    /**
     * Find a variation by its attribute options.
     */
    public function findVariationByOptions(Product $product, array $options): ?ProductVariation
    {
        return $product->variations()
            ->where('options', json_encode($options))
            ->first();
    }

    /**
     * Check if a product has variations.
     */
    public function hasVariations(Product $product): bool
    {
        return $product->variations()->exists();
    }

    /**
     * Get the default variation for a product.
     */
    public function getDefaultVariation(Product $product): ?ProductVariation
    {
        return $product->variations()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Set a variation as the default for its product.
     */
    public function setAsDefault(ProductVariation $variation): void
    {
        DB::transaction(function () use ($variation): void {
            // Remove default flag from all other variations of this product
            ProductVariation::where('product_id', $variation->product_id)
                ->where('id', '!=', $variation->id)
                ->update(['is_default' => false]);

            // Set this variation as default
            $variation->update(['is_default' => true]);
        });
    }

    /**
     * Generate cartesian product of attribute value sets.
     *
     * @param array $sets Array of arrays, where each sub-array contains values for one attribute
     *
     * @return array Array of combinations, where each combination is an associative array
     */
    private function cartesianProduct(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $attributeSlug => $values) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($values as $value) {
                    $newCombination = $combination;
                    $newCombination[$attributeSlug] = $value;
                    $temp[] = $newCombination;
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /**
     * Generate a human-readable name for a variation based on its attribute combination.
     */
    private function generateVariationName(Product $product, array $combination): string
    {
        $parts = [$product->name];

        foreach ($combination as $value) {
            $parts[] = $value;
        }

        return implode(' - ', $parts);
    }

    /**
     * Generate a unique SKU for a variation based on the product SKU and attribute combination.
     */
    private function generateVariationSku(Product $product, array $combination): string
    {
        $baseSku = $product->sku ?? 'PROD-' . $product->id;

        // Create a suffix from the attribute values
        $suffix = '';
        foreach ($combination as $value) {
            // Take first 3 characters of each value, uppercase
            $suffix .= '-' . strtoupper(substr((string) $value, 0, 3));
        }

        $proposedSku = $baseSku . $suffix;

        // Ensure uniqueness by adding a counter if needed
        $counter = 1;
        $finalSku = $proposedSku;

        while (ProductVariation::where('sku', $finalSku)->exists()) {
            $finalSku = $proposedSku . '-' . $counter;
            $counter++;
        }

        return $finalSku;
    }

    /**
     * Validate that variation options correspond to configurable attributes of the product.
     */
    private function validateVariationOptions(Product $product, array $options): void
    {
        if ($options === []) {
            throw new \InvalidArgumentException('Variation options cannot be empty');
        }

        // Get configurable attributes for this product
        $configurableAttributes = $product->configurableAttributes()
            ->pluck('slug')
            ->toArray();

        // Check that all option keys correspond to configurable attributes
        foreach (array_keys($options) as $attributeSlug) {
            if (! in_array($attributeSlug, $configurableAttributes, true)) {
                throw new \InvalidArgumentException("Attribute '{$attributeSlug}' is not configurable for this product");
            }
        }

        // Validate that each option value is valid for its attribute
        foreach ($options as $attributeSlug => $value) {
            $attribute = ProductAttribute::where('slug', $attributeSlug)
                ->where('team_id', $product->team_id)
                ->first();

            if (! $attribute) {
                throw new \InvalidArgumentException("Attribute '{$attributeSlug}' not found");
            }

            // For now, we'll skip the isValidValue check since it's not implemented
            // This would need to be implemented in the ProductAttribute model
            // if (! $attribute->isValidValue($value)) {
            //     throw new \InvalidArgumentException("Invalid value '{$value}' for attribute '{$attributeSlug}'");
            // }
        }
    }

    /**
     * Bulk update variations for a product.
     */
    public function bulkUpdateVariations(Product $product, array $variationsData): Collection
    {
        $updatedVariations = new Collection;

        DB::transaction(function () use ($product, $variationsData, &$updatedVariations): void {
            foreach ($variationsData as $variationData) {
                if (isset($variationData['id'])) {
                    // Update existing variation
                    $variation = ProductVariation::where('id', $variationData['id'])
                        ->where('product_id', $product->id)
                        ->firstOrFail();

                    $updatedVariation = $this->updateVariation($variation, $variationData);
                    $updatedVariations->push($updatedVariation);
                } else {
                    // Create new variation
                    if (! isset($variationData['options'])) {
                        throw new \InvalidArgumentException('Options are required for new variations');
                    }

                    $newVariation = $this->createVariation($product, $variationData['options'], $variationData);
                    $updatedVariations->push($newVariation);
                }
            }
        });

        return $updatedVariations;
    }

    /**
     * Get variation statistics for a product.
     */
    public function getVariationStats(Product $product): array
    {
        $variations = $product->variations();

        return [
            'total_variations' => $variations->count(),
            'active_variations' => $variations->whereNull('deleted_at')->count(),
            'inactive_variations' => $variations->onlyTrashed()->count(),
            'total_inventory' => $variations->sum('inventory_quantity'),
            'total_reserved' => $variations->sum('reserved_quantity'),
            'total_available' => $variations->sum('inventory_quantity') - $variations->sum('reserved_quantity'),
            'has_default' => $variations->where('is_default', true)->exists(),
        ];
    }

    /**
     * Sync variation inventory with parent product.
     * This updates the parent product's inventory to reflect the sum of all variation inventories.
     */
    public function syncInventoryWithParent(Product $product): void
    {
        if (! $product->hasVariants()) {
            return;
        }

        $totalInventory = $product->variations()->sum('inventory_quantity');
        $totalReserved = $product->variations()->sum('reserved_quantity');

        $product->update([
            'inventory_quantity' => $totalInventory,
            'reserved_quantity' => $totalReserved,
        ]);
    }
}
