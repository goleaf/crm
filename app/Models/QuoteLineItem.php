<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteDiscountType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount_value
 * @property QuoteDiscountType|null $discount_type
 * @property float $tax_rate
 * @property float $line_total
 * @property float $tax_total
 */
final class QuoteLineItem extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quote_id',
        'team_id',
        'product_id',
        'sku',
        'name',
        'description',
        'tax_category',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'tax_rate',
        'line_total',
        'tax_total',
        'sort_order',
        'is_custom',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_type' => QuoteDiscountType::class,
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'is_custom' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $lineItem): void {
            if ($lineItem->team_id === null) {
                $lineItem->team_id = $lineItem->quote?->team_id
                    ?? Quote::query()->whereKey($lineItem->quote_id)->value('team_id');
            }

            if ($lineItem->sort_order === 0 && $lineItem->quote !== null) {
                $lineItem->sort_order = (int) (($lineItem->quote->lineItems()->max('sort_order') ?? 0) + 1);
            }

            self::calculateTotals($lineItem);
        });

        self::saving(function (self $lineItem): void {
            self::calculateTotals($lineItem);
        });

        self::saved(function (self $lineItem): void {
            $lineItem->quote?->syncFinancials();
        });
    }

    private static function calculateTotals(self $lineItem): void
    {
        $lineItem->discount_type ??= QuoteDiscountType::PERCENT;

        $base = round(((float) $lineItem->quantity) * ((float) $lineItem->unit_price), 2);
        $discountType = $lineItem->discount_type ?? QuoteDiscountType::PERCENT;
        $discount = $discountType->calculate($base, (float) $lineItem->discount_value);
        $lineSubtotal = max($base - $discount, 0);
        $taxTotal = round($lineSubtotal * ((float) $lineItem->tax_rate / 100), 2);

        $lineItem->line_total = $lineSubtotal;
        $lineItem->tax_total = $taxTotal;
    }
}
