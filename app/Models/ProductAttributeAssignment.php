<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(\App\Policies\ProductAttributeAssignmentPolicy::class)]
final class ProductAttributeAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_attribute_id',
        'product_attribute_value_id',
        'custom_value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_value' => 'json',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductAttribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    /**
     * @return BelongsTo<ProductAttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }

    /**
     * Get the actual value for this assignment
     */
    public function getValue(): mixed
    {
        // If we have a predefined value, use it
        if ($this->attributeValue) {
            return $this->attributeValue->value;
        }

        // Otherwise use the custom value
        return $this->custom_value;
    }

    /**
     * Set the value for this assignment
     */
    public function setValue(mixed $value): void
    {
        $attribute = $this->attribute;

        if (! $attribute) {
            throw new \InvalidArgumentException('Attribute must be loaded to set value');
        }

        // Validate the value
        if (! $attribute->isValidValue($value)) {
            throw new \InvalidArgumentException('Invalid value for attribute type');
        }

        // Cast the value to the appropriate type
        $castedValue = $attribute->castValue($value);

        // For select/multi-select attributes, try to find a matching predefined value
        if ($attribute->requiresValues()) {
            if ($attribute->data_type === \App\Enums\ProductAttributeDataType::MULTI_SELECT) {
                // For multi-select, store as custom value (JSON array)
                $this->product_attribute_value_id = null;
                $this->custom_value = $castedValue;
            } else {
                // For single select, try to find matching predefined value
                $attributeValue = $attribute->values()
                    ->where('value', $castedValue)
                    ->first();

                if ($attributeValue) {
                    $this->product_attribute_value_id = $attributeValue->id;
                    $this->custom_value = null;
                } else {
                    // If no predefined value found, store as custom
                    $this->product_attribute_value_id = null;
                    $this->custom_value = $castedValue;
                }
            }
        } else {
            // For other types, always store as custom value
            $this->product_attribute_value_id = null;
            $this->custom_value = $castedValue;
        }
    }

    /**
     * Get the display value for this assignment
     */
    public function getDisplayValue(): string
    {
        $value = $this->getValue();

        if (is_array($value)) {
            return implode(', ', $value);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    protected static function booted(): void
    {
        self::saving(function (self $assignment): void {
            // Ensure we have either a predefined value or a custom value, but not both
            if ($assignment->product_attribute_value_id && $assignment->custom_value !== null) {
                throw new \InvalidArgumentException('Cannot have both predefined and custom value');
            }

            // Ensure we have at least one value
            if (! $assignment->product_attribute_value_id && $assignment->custom_value === null) {
                throw new \InvalidArgumentException('Must have either predefined or custom value');
            }

            // Validate the value if we have a custom value
            if ($assignment->custom_value !== null && $assignment->attribute && ! $assignment->attribute->isValidValue($assignment->custom_value)) {
                throw new \InvalidArgumentException('Invalid custom value for attribute type');
            }
        });
    }
}
