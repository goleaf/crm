<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductLifecycleStage;
use App\Enums\ProductRelationshipType;
use App\Enums\ProductStatus;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasTaxonomies;
    use HasTeam;
    use HasUniqueSlug;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'sku',
        'part_number',
        'manufacturer',
        'product_type',
        'status',
        'lifecycle_stage',
        'description',
        'price',
        'cost_price',
        'currency_code',
        'price_effective_from',
        'price_effective_to',
        'is_active',
        'is_bundle',
        'track_inventory',
        'inventory_quantity',
        'reserved_quantity',
        'custom_fields',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency_code' => 'USD',
        'status' => ProductStatus::ACTIVE,
        'lifecycle_stage' => ProductLifecycleStage::RELEASED,
        'product_type' => 'stocked',
        'cost_price' => 0,
        'is_active' => true,
        'is_bundle' => false,
        'track_inventory' => false,
        'inventory_quantity' => 0,
        'reserved_quantity' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'lifecycle_stage' => ProductLifecycleStage::class,
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_bundle' => 'boolean',
            'track_inventory' => 'boolean',
            'inventory_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'price_effective_from' => 'datetime',
            'price_effective_to' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<ProductCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'category_product', 'product_id', 'product_category_id')
            ->withTimestamps();
    }

    /**
     * Taxonomy-based product categories.
     *
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taxonomyCategories(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'product_category')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return BelongsToMany<ProductAttribute, $this>
     */
    public function configurableAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_configurable_attributes');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductDiscountRule, $this>
     */
    public function discountRules(): HasMany
    {
        return $this->hasMany(ProductDiscountRule::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductRelationship, $this>
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(ProductRelationship::class);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function crossSells(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::CROSS_SELL);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function upsells(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::UPSELL);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function bundleComponents(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::BUNDLE);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function dependencies(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::DEPENDENCY);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function alternatives(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::ALTERNATIVE);
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function accessories(): HasMany
    {
        return $this->relationships()->where('relationship_type', ProductRelationshipType::ACCESSORY);
    }

    /**
     * Get all related products for this product.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getRelatedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $relatedProductIds = $this->relationships()
            ->pluck('related_product_id')
            ->unique();

        return Product::whereIn('id', $relatedProductIds)->get();
    }

    /**
     * Get products that suggest this product as related.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getProductsThatSuggestThis(): \Illuminate\Database\Eloquent\Collection
    {
        $productIds = ProductRelationship::where('related_product_id', $this->id)
            ->pluck('product_id')
            ->unique();

        return Product::whereIn('id', $productIds)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductAttributeAssignment, $this>
     */
    public function attributeAssignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductVariation, $this>
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function isSellable(): bool
    {
        return $this->is_active === true
            && $this->status->isSellable()
            && $this->lifecycle_stage->isSellable();
    }

    /**
     * Check if the product allows new sales (not discontinued).
     */
    public function allowsNewSales(): bool
    {
        return $this->status->allowsNewSales() && $this->lifecycle_stage->allowsNewSales();
    }

    /**
     * Activate the product for sales.
     */
    public function activate(): void
    {
        $this->update([
            'status' => ProductStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate the product (prevent new sales but maintain historical data).
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => ProductStatus::INACTIVE,
            'is_active' => false,
        ]);
    }

    /**
     * Discontinue the product (prevent new sales, maintain historical data).
     */
    public function discontinue(): void
    {
        $this->update([
            'status' => ProductStatus::DISCONTINUED,
            'is_active' => false,
        ]);
    }

    /**
     * Set the product to draft status.
     */
    public function setToDraft(): void
    {
        $this->update([
            'status' => ProductStatus::DRAFT,
            'is_active' => false,
        ]);
    }

    /**
     * Transition the product to a new lifecycle stage.
     */
    public function transitionToLifecycleStage(ProductLifecycleStage $stage): void
    {
        $this->update(['lifecycle_stage' => $stage]);
    }

    public function priceFor(int $quantity = 1, ?Company $company = null, ?CarbonInterface $date = null): float
    {
        $date ??= \Illuminate\Support\Facades\Date::now();
        $baseWindowActive = ($this->price_effective_from === null || $date->greaterThanOrEqualTo($this->price_effective_from))
            && ($this->price_effective_to === null || $date->lessThanOrEqualTo($this->price_effective_to));
        $basePrice = $baseWindowActive ? (float) $this->price : 0.0;

        $tier = $this->priceTiers()
            ->activeOn($date)
            ->where('min_quantity', '<=', $quantity)
            ->where(fn (Builder $query): Builder => $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity))
            ->orderByDesc('min_quantity')
            ->first();

        if ($tier !== null) {
            $basePrice = (float) $tier->price;
        }

        $categoryIds = $this->categories()->pluck('product_categories.id');

        $rules = ProductDiscountRule::query()
            ->where('team_id', $this->team_id)
            ->activeOn($date)
            ->forQuantity($quantity)
            ->where(function (Builder $query) use ($categoryIds): void {
                $query->whereNull('product_id')
                    ->orWhere('product_id', $this->getKey());

                if ($categoryIds->isNotEmpty()) {
                    $query->orWhereIn('product_category_id', $categoryIds);
                }
            })
            ->where(function (Builder $query) use ($company): void {
                $query->whereNull('company_id');

                if ($company instanceof \App\Models\Company) {
                    $query->orWhere('company_id', $company->getKey());
                }
            })
            ->orderByDesc('priority')
            ->get();

        $bestPrice = $basePrice;

        foreach ($rules as $rule) {
            if (! $rule->appliesToProduct($this, $company, $quantity)) {
                continue;
            }

            $discounted = $rule->applyDiscount($basePrice);

            if ($discounted < $bestPrice) {
                $bestPrice = $discounted;
            }
        }

        return $bestPrice;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk(config('filesystems.default', 'public'))
            ->registerMediaConversions(function (Media $media): void {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->nonOptimized();
            });
    }

    public function availableInventory(): int
    {
        if ($this->hasVariants()) {
            $totalInventory = (int) $this->variations()->sum('inventory_quantity');
            $totalReserved = (int) $this->variations()->sum('reserved_quantity');

            return max(0, $totalInventory - $totalReserved);
        }

        return max(0, (int) $this->inventory_quantity - (int) $this->reserved_quantity);
    }

    public function hasVariants(): bool
    {
        return $this->variations()->exists();
    }

    /**
     * Get total inventory quantity (including variations).
     */
    public function getTotalInventory(): int
    {
        if ($this->hasVariants()) {
            return (int) $this->variations()->sum('inventory_quantity');
        }

        return (int) $this->inventory_quantity;
    }

    /**
     * Get total reserved quantity (including variations).
     */
    public function getTotalReserved(): int
    {
        if ($this->hasVariants()) {
            return (int) $this->variations()->sum('reserved_quantity');
        }

        return (int) $this->reserved_quantity;
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->availableInventory() > 0;
    }

    /**
     * Check if the product is low on stock.
     */
    public function isLowStock(int $threshold = 10): bool
    {
        if (! $this->track_inventory) {
            return false;
        }

        return $this->availableInventory() <= $threshold;
    }

    /**
     * Reserve inventory for this product.
     */
    public function reserveInventory(int $quantity): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if ($this->availableInventory() < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);

        return true;
    }

    /**
     * Release reserved inventory.
     */
    public function releaseInventory(int $quantity): void
    {
        if (! $this->track_inventory) {
            return;
        }

        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
    }

    /**
     * Adjust inventory quantity.
     */
    public function adjustInventory(int $adjustment, string $reason = 'Manual adjustment'): void
    {
        if (! $this->track_inventory) {
            return;
        }

        $newQuantity = max(0, $this->inventory_quantity + $adjustment);
        $this->update(['inventory_quantity' => $newQuantity]);

        // Create inventory adjustment record if the model exists
        if (class_exists(\App\Models\InventoryAdjustment::class)) {
            \App\Models\InventoryAdjustment::create([
                'team_id' => $this->team_id,
                'product_id' => $this->id,
                'adjustment_quantity' => $adjustment,
                'reason' => $reason,
                'previous_quantity' => $this->inventory_quantity - $adjustment,
                'new_quantity' => $newQuantity,
                'adjusted_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Get the bundle price for this product if it's a bundle.
     */
    public function getBundlePrice(): float
    {
        if (! $this->is_bundle) {
            return (float) $this->price;
        }

        $componentPrice = $this->bundleComponents()
            ->with('relatedProduct')
            ->get()
            ->sum(function (ProductRelationship $relationship): int|float {
                $componentPrice = $relationship->price_override ?? $relationship->relatedProduct->price;

                return $componentPrice * $relationship->quantity;
            });

        // Use the bundle's set price or the sum of components, whichever is lower
        return min((float) $this->price, $componentPrice);
    }

    /**
     * Check if this product can be added to quotes/orders based on status.
     */
    public function canBeAddedToQuote(): bool
    {
        return $this->allowsNewSales() && $this->isSellable();
    }

    /**
     * Get suggested products for cross-sell/upsell.
     */
    public function getSuggestedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $crossSellIds = $this->crossSells()->pluck('related_product_id');
        $upsellIds = $this->upsells()->pluck('related_product_id');

        $suggestedIds = $crossSellIds->merge($upsellIds)->unique();

        return Product::whereIn('id', $suggestedIds)
            ->where('status', ProductStatus::ACTIVE)
            ->get();
    }

    /**
     * Assign an attribute value to this product
     */
    public function assignAttribute(ProductAttribute $attribute, mixed $value): ProductAttributeAssignment
    {
        // Check if assignment already exists
        $assignment = $this->attributeAssignments()
            ->where('product_attribute_id', $attribute->id)
            ->first();

        if (!$assignment) {
            $assignment = new ProductAttributeAssignment([
                'product_id' => $this->id,
                'product_attribute_id' => $attribute->id,
            ]);
        }

        // Set the value using the assignment's setValue method
        $assignment->setValue($value);
        $assignment->save();

        return $assignment;
    }

    /**
     * Remove an attribute assignment from this product
     */
    public function removeAttribute(ProductAttribute $attribute): bool
    {
        return $this->attributeAssignments()
            ->where('product_attribute_id', $attribute->id)
            ->delete() > 0;
    }

    /**
     * Get the value of a specific attribute for this product
     */
    public function getAttributeValue(ProductAttribute $attribute): mixed
    {
        $assignment = $this->attributeAssignments()
            ->where('product_attribute_id', $attribute->id)
            ->with('attributeValue')
            ->first();

        return $assignment?->getValue();
    }

    /**
     * Check if this product has a specific attribute assigned
     */
    public function hasAttribute(ProductAttribute $attribute): bool
    {
        return $this->attributeAssignments()
            ->where('product_attribute_id', $attribute->id)
            ->exists();
    }

    /**
     * Get all attribute assignments with their values for display
     */
    public function getAttributesForDisplay(): array
    {
        return $this->attributeAssignments()
            ->with(['attribute', 'attributeValue'])
            ->get()
            ->map(function (ProductAttributeAssignment $assignment) {
                return [
                    'attribute' => $assignment->attribute,
                    'value' => $assignment->getValue(),
                    'display_value' => $assignment->getDisplayValue(),
                ];
            })
            ->toArray();
    }

    /**
     * Bulk assign multiple attributes to this product
     */
    public function assignAttributes(array $attributeValues): void
    {
        foreach ($attributeValues as $attributeId => $value) {
            $attribute = ProductAttribute::find($attributeId);
            if ($attribute) {
                $this->assignAttribute($attribute, $value);
            }
        }
    }

    protected static function booted(): void
    {
        self::creating(function (self $product): void {
            if ($product->team_id === null && auth('web')->check()) {
                $product->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            $product->currency_code ??= config('company.default_currency', 'USD');
        });
    }
}
