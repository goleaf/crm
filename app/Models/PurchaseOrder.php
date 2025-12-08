<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\ProcessApprovalStatus;
use App\Enums\PurchaseOrderReceiptType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasReferenceNumbering;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;
use MohamedSaid\Referenceable\Traits\HasReference;

/**
 * @property PurchaseOrderStatus $status
 */
final class PurchaseOrder extends Model
{
    use HasCreator;
    use HasFactory;
    use HasReference;
    use HasReferenceNumbering;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    protected string $referenceColumn = 'number';

    protected string $referenceStrategy = 'template';

    /**
     * @var array{format: string, sequence_length: int}
     */
    protected array $referenceTemplate = [
        'format' => 'PO-{YEAR}-{SEQ}',
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
        'team_id',
        'creator_id',
        'vendor_id',
        'company_id',
        'order_id',
        'sequence',
        'number',
        'status',
        'ordered_at',
        'expected_delivery_date',
        'approved_at',
        'issued_at',
        'last_received_at',
        'closed_at',
        'cancelled_at',
        'payment_terms',
        'shipping_terms',
        'ship_method',
        'ship_to_address',
        'bill_to_address',
        'currency_code',
        'fx_rate',
        'subtotal',
        'tax_total',
        'freight_total',
        'fee_total',
        'total',
        'received_cost',
        'outstanding_commitment',
        'notes',
        'terms',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PurchaseOrderStatus::DRAFT,
        'currency_code' => 'USD',
        'fx_rate' => 1,
        'subtotal' => 0,
        'tax_total' => 0,
        'freight_total' => 0,
        'fee_total' => 0,
        'total' => 0,
        'received_cost' => 0,
        'outstanding_commitment' => 0,
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * Keep reference generation coordinated with registerNumberIfMissing so counters stay in sync.
     */
    protected static function bootHasReference(): void
    {
        // Override trait boot hook to control generation timing.
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'ordered_at' => 'date',
            'expected_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'last_received_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fx_rate' => 'decimal:6',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'freight_total' => 'decimal:2',
            'fee_total' => 'decimal:2',
            'total' => 'decimal:2',
            'received_cost' => 'decimal:2',
            'outstanding_commitment' => 'decimal:2',
            'creation_source' => CreationSource::class,
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PurchaseOrderLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineItem::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PurchaseOrderReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PurchaseOrderApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class);
    }

    public function registerNumberIfMissing(): void
    {
        if ($this->team_id === null) {
            return;
        }

        $this->primeReferenceCounter('ordered_at');

        if ($this->number === null) {
            $this->number = $this->generateReference();
        }

        if ($this->sequence === null) {
            $sequence = $this->extractSequenceNumber($this->number);

            if ($sequence !== null) {
                $this->sequence = $sequence;
            }
        }
    }

    /**
     * Recalculate totals, commitments, and receipt-driven status.
     */
    public function syncFinancials(): void
    {
        $lineItems = $this->lineItems()->get([
            'id',
            'quantity',
            'unit_cost',
            'tax_rate',
        ]);

        $receivedQuantities = $this->receipts()
            ->selectRaw('purchase_order_line_item_id, SUM(quantity * CASE WHEN receipt_type = ? THEN -1 ELSE 1 END) as total_quantity', [
                PurchaseOrderReceiptType::RETURN->value,
            ])
            ->groupBy('purchase_order_line_item_id')
            ->pluck('total_quantity', 'purchase_order_line_item_id');

        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lineItems as $item) {
            $lineTotal = round(((float) $item->quantity) * ((float) $item->unit_cost), 2);
            $lineTax = round($lineTotal * ((float) $item->tax_rate / 100), 2);

            $subtotal += $lineTotal;
            $taxTotal += $lineTax;

            $received = max((float) ($receivedQuantities[$item->id] ?? 0), 0);

            $item->forceFill([
                'line_total' => $lineTotal,
                'tax_total' => $lineTax,
                'received_quantity' => $received,
            ])->saveQuietly();
        }

        $freight = (float) $this->freight_total;
        $fees = (float) $this->fee_total;
        $committedTotal = max(round($subtotal + $taxTotal + $freight + $fees, 2), 0);

        $receipts = $this->receipts()->get(['line_total', 'receipt_type', 'received_at']);
        $receivedCost = max(round($receipts->sum(
            fn (PurchaseOrderReceipt $receipt): float => $receipt->signedTotal()
        ), 2), 0);

        $outstanding = max(round($committedTotal - $receivedCost, 2), 0);
        $lastReceivedAt = $receipts->max('received_at');

        $status = $this->status ?? PurchaseOrderStatus::DRAFT;

        if (! in_array($status, [PurchaseOrderStatus::CANCELLED, PurchaseOrderStatus::CLOSED], true)) {
            $allReceived = $lineItems->isNotEmpty()
                && $lineItems->every(fn (PurchaseOrderLineItem $item): bool => (float) $item->received_quantity >= (float) $item->quantity);

            $hasReceipts = $receivedCost > 0;

            if ($allReceived) {
                $status = PurchaseOrderStatus::RECEIVED;
            } elseif ($hasReceipts) {
                $status = PurchaseOrderStatus::PARTIALLY_RECEIVED;
            }
        }

        self::withoutEvents(function () use ($subtotal, $taxTotal, $committedTotal, $receivedCost, $outstanding, $status, $lastReceivedAt): void {
            $this->forceFill([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $committedTotal,
                'received_cost' => $receivedCost,
                'outstanding_commitment' => $outstanding,
                'status' => $status,
                'last_received_at' => $lastReceivedAt,
            ])->saveQuietly();
        });
    }

    /**
     * Update the purchase order status based on approval outcomes.
     */
    public function syncApprovalState(): void
    {
        $approvals = $this->approvals()->get(['status', 'decided_at']);

        if ($approvals->isEmpty()) {
            return;
        }

        if (in_array($this->status, [PurchaseOrderStatus::CANCELLED, PurchaseOrderStatus::CLOSED], true)) {
            return;
        }

        $allApproved = $approvals->every(fn (PurchaseOrderApproval $approval): bool => $approval->status === ProcessApprovalStatus::APPROVED);
        $anyPending = $approvals->contains(fn (PurchaseOrderApproval $approval): bool => in_array($approval->status, [
            ProcessApprovalStatus::PENDING,
            ProcessApprovalStatus::ESCALATED,
        ], true));
        $anyRejected = $approvals->contains(fn (PurchaseOrderApproval $approval): bool => $approval->status === ProcessApprovalStatus::REJECTED);

        $nextStatus = $this->status ?? PurchaseOrderStatus::DRAFT;
        $approvedAt = null;

        if ($anyRejected) {
            $nextStatus = PurchaseOrderStatus::CANCELLED;
        } elseif ($allApproved) {
            $nextStatus = PurchaseOrderStatus::APPROVED;
            $approvedAt = $approvals->max('decided_at') ?? Date::now();
        } elseif ($anyPending) {
            $nextStatus = PurchaseOrderStatus::PENDING_APPROVAL;
        }

        self::withoutEvents(function () use ($nextStatus, $approvedAt): void {
            $this->forceFill([
                'status' => $nextStatus,
                'approved_at' => $approvedAt,
            ])->saveQuietly();
        });
    }

    protected static function booted(): void
    {
        self::creating(function (self $purchaseOrder): void {
            if ($purchaseOrder->team_id === null && auth('web')->check()) {
                $purchaseOrder->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            if ($purchaseOrder->creator_id === null && auth('web')->check()) {
                $purchaseOrder->creator_id = auth('web')->id();
            }

            $purchaseOrder->ordered_at ??= Date::now();
            $purchaseOrder->creation_source ??= CreationSource::WEB;
            $purchaseOrder->currency_code ??= config('company.default_currency', 'USD');
            $purchaseOrder->registerNumberIfMissing();
        });

        self::saving(function (self $purchaseOrder): void {
            $purchaseOrder->registerNumberIfMissing();
        });
    }
}
