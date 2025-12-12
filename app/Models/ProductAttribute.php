<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductAttributeDataType;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(\App\Policies\ProductAttributePolicy::class)]
final class ProductAttribute extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeFactory> */
    use HasFactory;

    use HasTeam;
    use HasUniqueSlug;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'data_type',
        'is_configurable',
        'is_filterable',
        'is_required',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_type' => ProductAttributeDataType::class,
            'is_configurable' => 'boolean',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Product, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function configurableForProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_configurable_attributes');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductAttributeAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class);
    }

    /**
     * Check if this attribute is configurable for variations
     */
    public function isConfigurable(): bool
    {
        return $this->is_configurable;
    }

    /**
     * Check if this attribute is filterable in searches
     */
    public function isFilterable(): bool
    {
        return $this->is_filterable;
    }

    /**
     * Check if this attribute requires predefined values
     */
    public function requiresValues(): bool
    {
        return $this->data_type->requiresValues();
    }

    /**
     * Validate a value against this attribute's data type
     */
    public function validateValue(mixed $value): bool
    {
        return $this->data_type->validateValue($value);
    }

    /**
     * Cast a value to the appropriate type for this attribute
     */
    public function castValue(mixed $value): mixed
    {
        return $this->data_type->castValue($value);
    }

    /**
     * Get the valid values for this attribute (for select/multi-select types)
     */
    public function getValidValues(): array
    {
        if (!$this->requiresValues()) {
            return [];
        }

        return $this->values()->orderBy('sort_order')->pluck('value')->toArray();
    }

    /**
     * Check if a value is valid for this attribute
     */
    public function isValidValue(mixed $value): bool
    {
        // First check data type validation
        if (!$this->validateValue($value)) {
            return false;
        }

        // For select/multi-select, check against predefined values
        if ($this->requiresValues()) {
            $validValues = $this->getValidValues();
            
            if ($this->data_type === ProductAttributeDataType::MULTI_SELECT) {
                if (!is_array($value)) {
                    return false;
                }
                return collect($value)->every(fn($v) => in_array($v, $validValues, true));
            }
            
            return in_array($value, $validValues, true);
        }

        return true;
    }

    protected static function booted(): void
    {
        self::creating(function (self $attribute): void {
            if ($attribute->team_id === null && auth('web')->check()) {
                $attribute->team_id = auth('web')->user()?->currentTeam?->getKey();
            }
        });
    }
}
