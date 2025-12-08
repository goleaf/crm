<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderReceiptType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use MohamedSaid\Referenceable\Traits\HasReference;

/**
 * @property PurchaseOrderReceiptType $receipt_type
 * @property float $quantity
 * @property float $unit_cost
 * @property float $line_total
 * @property Carbon|null $received_at
 */
final class PurchaseOrderReceipt extends Model
{
    use HasFactory;
    use HasReference;
    use HasTeam;

    protected string $referenceColumn = 'reference';

    protected string $referenceStrategy = 'template';

    /**
     * @var array{format: string, sequence_length: int}
     */
    protected array $referenceTemplate = [
        'format' => 'POR-{YEAR}-{SEQ}',
        'sequence_length' => 5,
    ];

    /**
     * @var array{min_digits: int, reset_frequency: string}
     */
    protected array $referenceSequential = [
        'min_digits' => 5,
        'reset_frequency' => 'yearly',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'purchase_order_line_item_id',
        'team_id',
        'received_by_id',
        'receipt_type',
        'quantity',
        'unit_cost',
        'line_total',
        'received_at',
        'reference',
        'notes',
    ];

    /**
     * Reference assignment is coordinated during lifecycle hooks.
     */
    protected static function bootHasReference(): void
    {
        // Avoid the trait's automatic generation; we seed references manually.
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'receipt_type' => PurchaseOrderReceiptType::class,
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'received_at' => 'datetime',
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
     * @return BelongsTo<PurchaseOrderLineItem, $this>
     */
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLineItem::class, 'purchase_order_line_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function registerReferenceIfMissing(): void
    {
        if ($this->team_id === null) {
            return;
        }

        if ($this->reference === null) {
            $this->reference = $this->generateReference();
        }
    }

    public function signedTotal(): float
    {
        return ((float) $this->line_total) * $this->receipt_type->multiplier();
    }

    protected static function booted(): void
    {
        self::creating(function (self $receipt): void {
            if ($receipt->purchase_order_id !== null) {
                $receipt->team_id = $receipt->purchaseOrder()->withoutTrashed()->value('team_id') ?? $receipt->team_id;
            } elseif ($receipt->purchase_order_line_item_id !== null) {
                $receipt->purchase_order_id = $receipt->lineItem()->withoutTrashed()->value('purchase_order_id');
            }

            if ($receipt->team_id === null && $receipt->purchase_order_id !== null) {
                $receipt->team_id = $receipt->purchaseOrder()->withoutTrashed()->value('team_id');
            }

            if ($receipt->received_by_id === null && auth('web')->check()) {
                $receipt->received_by_id = auth('web')->id();
            }

            $receipt->received_at ??= Date::now();
            $receipt->registerReferenceIfMissing();
        });

        self::saving(function (self $receipt): void {
            $receipt->registerReferenceIfMissing();
            $receipt->line_total ??= round(((float) $receipt->quantity) * ((float) $receipt->unit_cost), 2);
        });

        $refresh = static fn (self $receipt): void => $receipt->purchaseOrder()->withoutTrashed()->first()?->syncFinancials();

        self::saved($refresh);
        self::deleted($refresh);
    }
}
