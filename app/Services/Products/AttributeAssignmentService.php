<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttributeAssignmentService
{
    /**
     * Assign multiple attributes to a product in a transaction
     */
    public function assignAttributesToProduct(Product $product, array $attributeValues): Collection
    {
        return DB::transaction(function () use ($product, $attributeValues) {
            $assignments = collect();

            foreach ($attributeValues as $attributeId => $value) {
                $attribute = ProductAttribute::findOrFail($attributeId);
                $assignment = $product->assignAttribute($attribute, $value);
                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    /**
     * Update attribute assignments for a product
     */
    public function updateProductAttributes(Product $product, array $attributeValues): Collection
    {
        return DB::transaction(function () use ($product, $attributeValues) {
            // Remove existing assignments that are not in the new data
            $existingAttributeIds = $product->attributeAssignments()->pluck('product_attribute_id');
            $newAttributeIds = collect(array_keys($attributeValues));
            
            $toRemove = $existingAttributeIds->diff($newAttributeIds);
            if ($toRemove->isNotEmpty()) {
                $product->attributeAssignments()
                    ->whereIn('product_attribute_id', $toRemove)
                    ->delete();
            }

            // Assign or update the new attributes
            return $this->assignAttributesToProduct($product, $attributeValues);
        });
    }

    /**
     * Copy attribute assignments from one product to another
     */
    public function copyAttributeAssignments(Product $sourceProduct, Product $targetProduct): Collection
    {
        return DB::transaction(function () use ($sourceProduct, $targetProduct) {
            $assignments = collect();

            foreach ($sourceProduct->attributeAssignments as $sourceAssignment) {
                $assignment = $targetProduct->assignAttribute(
                    $sourceAssignment->attribute,
                    $sourceAssignment->getValue()
                );
                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    /**
     * Validate attribute assignments for a product
     */
    public function validateAttributeAssignments(Product $product): array
    {
        $errors = [];

        foreach ($product->attributeAssignments as $assignment) {
            $attribute = $assignment->attribute;
            $value = $assignment->getValue();

            // Check if required attributes have values
            if ($attribute->is_required && ($value === null || $value === '')) {
                $errors[] = "Required attribute '{$attribute->name}' is missing a value";
            }

            // Validate the value against the attribute type
            if ($value !== null && !$attribute->isValidValue($value)) {
                $errors[] = "Invalid value for attribute '{$attribute->name}': {$assignment->getDisplayValue()}";
            }
        }

        // Check for missing required attributes
        $requiredAttributes = ProductAttribute::where('team_id', $product->team_id)
            ->where('is_required', true)
            ->get();

        $assignedAttributeIds = $product->attributeAssignments->pluck('product_attribute_id');

        foreach ($requiredAttributes as $requiredAttribute) {
            if (!$assignedAttributeIds->contains($requiredAttribute->id)) {
                $errors[] = "Required attribute '{$requiredAttribute->name}' is not assigned";
            }
        }

        return $errors;
    }

    /**
     * Get products that have a specific attribute value
     */
    public function getProductsWithAttributeValue(ProductAttribute $attribute, mixed $value): Collection
    {
        $query = Product::whereHas('attributeAssignments', function ($query) use ($attribute, $value) {
            $query->where('product_attribute_id', $attribute->id);

            if ($attribute->requiresValues()) {
                // For select/multi-select, check both predefined and custom values
                $query->where(function ($subQuery) use ($value) {
                    $subQuery->whereHas('attributeValue', function ($valueQuery) use ($value) {
                        $valueQuery->where('value', $value);
                    })->orWhere('custom_value', $value);
                });
            } else {
                // For other types, check custom value
                $query->where('custom_value', $value);
            }
        });

        return $query->get();
    }

    /**
     * Get all unique values for an attribute across all products
     */
    public function getUniqueAttributeValues(ProductAttribute $attribute): array
    {
        $values = [];

        // Get predefined values
        if ($attribute->requiresValues()) {
            $values = array_merge($values, $attribute->getValidValues());
        }

        // Get custom values from assignments
        $customValues = ProductAttributeAssignment::where('product_attribute_id', $attribute->id)
            ->whereNotNull('custom_value')
            ->pluck('custom_value')
            ->filter()
            ->unique()
            ->toArray();

        $values = array_merge($values, $customValues);

        return array_unique($values);
    }

    /**
     * Bulk update attribute assignments for multiple products
     */
    public function bulkUpdateAttributes(Collection $products, array $attributeValues): void
    {
        DB::transaction(function () use ($products, $attributeValues) {
            foreach ($products as $product) {
                $this->assignAttributesToProduct($product, $attributeValues);
            }
        });
    }
}