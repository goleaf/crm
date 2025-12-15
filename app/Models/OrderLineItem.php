<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\OrderLineItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float $quantity
 * @property float $unit_price
 * @property float $fulfilled_quantity
 * @property float $line_total
 * @property float $tax_total
 */
final class OrderLineItem extends Model
{
    /** @use HasFactory<OrderLineItemFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'team_id',
        'name',
        'description',
        'quantity',
        'fulfilled_quantity',
        'unit_price',
        'tax_rate',
        'line_total',
        'tax_total',
        'sort_order',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'fulfilled_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<PurchaseOrderLineItem, $this>
     */
    public function purchaseOrderLineItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineItem::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $lineItem): void {
            if ($lineItem->team_id === null && $lineItem->order !== null) {
                $lineItem->team_id = $lineItem->order->team_id;
            }

            if ($lineItem->sort_order === 0 && $lineItem->order !== null) {
                $lineItem->sort_order = (int) (($lineItem->order->lineItems()->max('sort_order') ?? 0) + 1);
            }

            self::normalizeFulfillment($lineItem);
            self::calculateTotals($lineItem);
        });

        self::saving(function (self $lineItem): void {
            self::normalizeFulfillment($lineItem);
            self::calculateTotals($lineItem);
        });

        self::saved(function (self $lineItem): void {
            $lineItem->order?->syncFinancials();
        });
    }

    private static function calculateTotals(self $lineItem): void
    {
        $lineTotal = round(((float) $lineItem->quantity) * ((float) $lineItem->unit_price), 2);
        $taxTotal = round($lineTotal * ((float) $lineItem->tax_rate / 100), 2);

        $lineItem->line_total = $lineTotal;
        $lineItem->tax_total = $taxTotal;
    }

    private static function normalizeFulfillment(self $lineItem): void
    {
        $quantity = max((float) $lineItem->quantity, 0);
        $fulfilled = max((float) $lineItem->fulfilled_quantity, 0);

        $lineItem->fulfilled_quantity = min($fulfilled, $quantity);
    }
}
