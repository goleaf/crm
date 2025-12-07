<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasTeam;
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
        'custom_fields',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency_code' => 'USD',
        'status' => 'active',
        'lifecycle_stage' => 'released',
        'product_type' => 'stocked',
        'cost_price' => 0,
        'is_active' => true,
        'is_bundle' => false,
        'track_inventory' => false,
        'inventory_quantity' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_bundle' => 'boolean',
            'track_inventory' => 'boolean',
            'inventory_quantity' => 'integer',
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
     * @return BelongsToMany<ProductAttribute, $this>
     */
    public function configurableAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_configurable_attributes');
    }

    /**
     * @return HasMany<ProductPriceTier>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    /**
     * @return HasMany<ProductDiscountRule>
     */
    public function discountRules(): HasMany
    {
        return $this->hasMany(ProductDiscountRule::class);
    }

    /**
     * @return HasMany<ProductRelationship>
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
        return $this->relationships()->where('relationship_type', 'cross_sell');
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function upsells(): HasMany
    {
        return $this->relationships()->where('relationship_type', 'upsell');
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function bundleComponents(): HasMany
    {
        return $this->relationships()->where('relationship_type', 'bundle');
    }

    /**
     * @return HasMany<ProductRelationship>
     */
    public function dependencies(): HasMany
    {
        return $this->relationships()->where('relationship_type', 'dependency');
    }

    /**
     * @return HasMany<ProductAttributeAssignment>
     */
    public function attributeAssignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class);
    }

    /**
     * @return HasMany<ProductVariation>
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function isSellable(): bool
    {
        return $this->is_active === true
            && $this->status === 'active'
            && \in_array($this->lifecycle_stage, ['released', 'active'], true);
    }

    public function priceFor(int $quantity = 1, ?Company $company = null, ?CarbonInterface $date = null): float
    {
        $date ??= Carbon::now();
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
        if ($this->variations()->exists()) {
            return (int) $this->variations()->sum('inventory_quantity');
        }

        return (int) $this->inventory_quantity;
    }

    public function hasVariants(): bool
    {
        return $this->variations()->exists();
    }

    protected static function booted(): void
    {
        self::creating(function (self $product): void {
            if ($product->team_id === null && auth('web')->check()) {
                $product->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            $product->currency_code ??= config('company.default_currency', 'USD');
            $product->slug ??= self::generateUniqueSlug($product->name ?? '', $product->team_id);
        });

        self::saving(function (self $product): void {
            $product->slug ??= self::generateUniqueSlug($product->name ?? '', $product->team_id);
        });
    }

    private static function generateUniqueSlug(string $name, ?int $teamId): string
    {
        $baseSlug = Str::slug($name) ?: Str::random(6);
        $slug = $baseSlug;
        $suffix = 1;
        $team = $teamId ?? 0;

        while (self::where('team_id', $team)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
