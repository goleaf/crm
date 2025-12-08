<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Services\Tenancy\CurrentTeamResolver;
use Carbon\CarbonInterface;
use Database\Factories\ProductPriceTierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $product_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property float $price
 * @property string $currency_code
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
final class ProductPriceTier extends Model
{
    /** @use HasFactory<ProductPriceTierFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'product_id',
        'min_quantity',
        'max_quantity',
        'price',
        'currency_code',
        'starts_at',
        'ends_at',
        'label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function activeOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where(fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $date))
            ->where(fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $date));
    }

    protected static function booted(): void
    {
        self::creating(function (self $tier): void {
            if ($tier->team_id === null) {
                $tier->team_id = $tier->product?->team_id
                    ?? Product::withoutGlobalScopes()->whereKey($tier->product_id)->value('team_id')
                    ?? CurrentTeamResolver::resolveId();
            }

            $tier->currency_code ??= $tier->product?->currency_code
                ?? Product::withoutGlobalScopes()->whereKey($tier->product_id)->value('currency_code')
                ?? config('company.default_currency', 'USD');
            $tier->min_quantity = max((int) ($tier->min_quantity ?: 1), 1);
        });

        self::saving(function (self $tier): void {
            $tier->team_id ??= $tier->product?->team_id
                ?? Product::withoutGlobalScopes()->whereKey($tier->product_id)->value('team_id')
                ?? CurrentTeamResolver::resolveId();
        });
    }
}
