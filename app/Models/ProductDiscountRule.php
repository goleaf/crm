<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteDiscountType;
use App\Models\Concerns\HasTeam;
use App\Services\Tenancy\CurrentTeamResolver;
use Carbon\CarbonInterface;
use Database\Factories\ProductDiscountRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string            $name
 * @property string            $scope
 * @property QuoteDiscountType $discount_type
 * @property float             $discount_value
 * @property int               $min_quantity
 * @property int|null          $max_quantity
 * @property Carbon|null       $starts_at
 * @property Carbon|null       $ends_at
 * @property bool              $is_active
 */
final class ProductDiscountRule extends Model
{
    /** @use HasFactory<ProductDiscountRuleFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'product_id',
        'product_category_id',
        'company_id',
        'name',
        'scope',
        'discount_type',
        'discount_value',
        'min_quantity',
        'max_quantity',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => QuoteDiscountType::class,
            'discount_value' => 'decimal:2',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'priority' => 'integer',
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
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function activeOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $date))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $date));
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forQuantity(Builder $query, int $quantity): Builder
    {
        return $query
            ->where('min_quantity', '<=', $quantity)
            ->where(fn (Builder $q) => $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity));
    }

    public function appliesToProduct(Product $product, ?Company $company, int $quantity): bool
    {
        if ($this->product_id !== null && $this->product_id !== $product->getKey()) {
            return false;
        }

        if ($this->product_category_id !== null) {
            $categoryMatch = $product
                ->categories()
                ->whereKey($this->product_category_id)
                ->exists();

            if (! $categoryMatch) {
                return false;
            }
        }

        if ($this->company_id !== null && $company?->getKey() !== $this->company_id) {
            return false;
        }

        if ($quantity < ($this->min_quantity ?? 1)) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    public function applyDiscount(float $basePrice): float
    {
        $discountType = $this->discount_type ?? QuoteDiscountType::PERCENT;
        $discountAmount = $discountType->calculate($basePrice, (float) $this->discount_value);

        return max(round($basePrice - $discountAmount, 2), 0);
    }

    protected static function booted(): void
    {
        self::creating(function (self $rule): void {
            if ($rule->team_id === null) {
                $rule->team_id = $rule->product?->team_id
                    ?? Product::withoutGlobalScopes()->whereKey($rule->product_id)->value('team_id')
                    ?? $rule->category?->team_id
                    ?? CurrentTeamResolver::resolveId();
            }
        });

        self::saving(function (self $rule): void {
            if ($rule->team_id === null) {
                $rule->team_id = $rule->product?->team_id
                    ?? Product::withoutGlobalScopes()->whereKey($rule->product_id)->value('team_id')
                    ?? $rule->category?->team_id
                    ?? CurrentTeamResolver::resolveId();
            }
        });
    }
}
