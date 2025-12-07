<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float $quantity
 * @property float $unit_cost
 * @property float $tax_rate
 * @property float $line_total
 * @property float $tax_total
 * @property float $received_quantity
 */
final class PurchaseOrderLineItem extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'team_id',
        'name',
        'description',
        'quantity',
        'received_quantity',
        'unit_cost',
        'tax_rate',
        'line_total',
        'tax_total',
        'order_line_item_id',
        'expected_receipt_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'expected_receipt_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<OrderLineItem, $this>
     */
    public function orderLineItem(): BelongsTo
    {
        return $this->belongsTo(OrderLineItem::class);
    }

    /**
     * @return HasMany<PurchaseOrderReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class, 'purchase_order_line_item_id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $lineItem): void {
            if ($lineItem->purchase_order_id !== null) {
                $lineItem->team_id = $lineItem->purchaseOrder()->withoutTrashed()->value('team_id') ?? $lineItem->team_id;
            }
        });

        $refresh = static fn (self $lineItem): void => $lineItem->purchaseOrder()->withoutTrashed()->first()?->syncFinancials();

        self::saved($refresh);
        self::deleted($refresh);
    }
}
