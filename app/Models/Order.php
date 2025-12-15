<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\InvoicePaymentStatus;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasReferenceNumbering;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use App\Observers\OrderObserver;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use MohamedSaid\Referenceable\Traits\HasReference;

/**
 * @property OrderStatus            $status
 * @property OrderFulfillmentStatus $fulfillment_status
 */
#[ObservedBy(OrderObserver::class)]
final class Order extends Model
{
    use HasCreator;

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasNotesAndNotables;
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
        'format' => 'ORD-{YEAR}-{SEQ}',
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
        'company_id',
        'contact_id',
        'opportunity_id',
        'quote_id',
        'invoice_template_key',
        'quote_reference',
        'sequence',
        'number',
        'status',
        'fulfillment_status',
        'ordered_at',
        'fulfillment_due_at',
        'fulfilled_at',
        'payment_terms',
        'currency_code',
        'fx_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'balance_due',
        'paid_total',
        'invoiced_total',
        'expected_delivery_date',
        'notes',
        'terms',
        'line_items',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => OrderStatus::DRAFT,
        'fulfillment_status' => OrderFulfillmentStatus::PENDING,
        'creation_source' => CreationSource::WEB,
        'currency_code' => 'USD',
        'fx_rate' => 1,
        'discount_total' => 0,
    ];

    /**
     * Referenceable handles generation via registerNumberIfMissing to keep counters seeded.
     */
    protected static function bootHasReference(): void
    {
        // Intentionally override the trait's boot to avoid early auto-generation.
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment_status' => OrderFulfillmentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'invoiced_total' => 'decimal:2',
            'line_items' => 'array',
            'expected_delivery_date' => 'date',
            'ordered_at' => 'date',
            'fulfillment_due_at' => 'date',
            'fulfilled_at' => 'datetime',
            'creation_source' => CreationSource::class,
            'fx_rate' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'contact_id');
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OrderLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
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
     * Recalculate totals, fulfillment status, and payment/ledger information.
     */
    public function syncFinancials(): void
    {
        $lineItems = $this->lineItems()->get([
            'quantity',
            'unit_price',
            'tax_rate',
            'fulfilled_quantity',
        ]);

        if ($lineItems->isEmpty() && is_array($this->line_items)) {
            $lineItems = collect($this->line_items)->map(fn (array $item): OrderLineItem => tap(new OrderLineItem, function (OrderLineItem $line) use ($item): void {
                $line->quantity = (float) ($item['quantity'] ?? 0);
                $line->unit_price = (float) ($item['unit_price'] ?? 0);
                $line->tax_rate = (float) ($item['tax_rate'] ?? 0);
                $line->fulfilled_quantity = (float) ($item['fulfilled_quantity'] ?? 0);
            }));
        }

        $subtotal = $lineItems->sum(fn (OrderLineItem $item): float => round(((float) $item->quantity) * ((float) $item->unit_price), 2));

        $taxTotal = $lineItems->sum(function (OrderLineItem $item): float {
            $lineTotal = ((float) $item->quantity) * ((float) $item->unit_price);

            return round($lineTotal * ((float) $item->tax_rate / 100), 2);
        });

        $discountTotal = (float) $this->discount_total;
        $total = max(round($subtotal - $discountTotal + $taxTotal, 2), 0);

        $invoicedTotal = (float) $this->invoices()->sum('total');
        $paidTotal = (float) InvoicePayment::query()
            ->where('status', InvoicePaymentStatus::COMPLETED)
            ->whereHas('invoice', fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query->where('order_id', $this->getKey()))
            ->sum('amount');

        $balance = max(round(($invoicedTotal ?: $total) - $paidTotal, 2), 0);

        $fulfillmentStatus = $this->determineFulfillmentStatus($lineItems);
        $previousFulfillmentStatus = $this->fulfillment_status ?? OrderFulfillmentStatus::PENDING;

        $nextStatus = $this->status ?? OrderStatus::DRAFT;

        if ($nextStatus !== OrderStatus::CANCELLED && $fulfillmentStatus === OrderFulfillmentStatus::FULFILLED) {
            $nextStatus = OrderStatus::FULFILLED;
        } elseif ($nextStatus !== OrderStatus::CANCELLED && $this->invoices()->exists()) {
            $nextStatus = OrderStatus::INVOICED;
        }

        self::withoutEvents(function () use ($subtotal, $taxTotal, $discountTotal, $total, $invoicedTotal, $paidTotal, $balance, $fulfillmentStatus, $previousFulfillmentStatus, $nextStatus): void {
            $fulfilledAt = $this->fulfilled_at;

            if ($fulfillmentStatus === OrderFulfillmentStatus::FULFILLED && $previousFulfillmentStatus !== OrderFulfillmentStatus::FULFILLED) {
                $fulfilledAt ??= Date::now();
            }

            $this->forceFill([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'invoiced_total' => $invoicedTotal ?: $total,
                'paid_total' => $paidTotal,
                'balance_due' => $balance,
                'fulfillment_status' => $fulfillmentStatus,
                'fulfilled_at' => $fulfilledAt,
                'status' => $nextStatus,
            ])->saveQuietly();
        });
    }

    private function determineFulfillmentStatus(Collection $lineItems): OrderFulfillmentStatus
    {
        if ($lineItems->isEmpty()) {
            return OrderFulfillmentStatus::PENDING;
        }

        $totalQuantity = $lineItems->sum(fn (OrderLineItem $item): float => (float) $item->quantity);
        $fulfilledQuantity = $lineItems->sum(fn (OrderLineItem $item): float => min((float) $item->fulfilled_quantity, (float) $item->quantity));

        if ($fulfilledQuantity <= 0) {
            return OrderFulfillmentStatus::PENDING;
        }

        if (abs($fulfilledQuantity - $totalQuantity) < 0.0001) {
            return OrderFulfillmentStatus::FULFILLED;
        }

        return OrderFulfillmentStatus::PARTIAL;
    }

    /**
     * Link the order to an invoice and sync ledger/fulfillment state.
     */
    public function markInvoiced(Invoice $invoice): void
    {
        self::withoutEvents(function () use ($invoice): void {
            $this->forceFill([
                'status' => OrderStatus::INVOICED,
                'invoiced_total' => (float) $invoice->total,
            ])->saveQuietly();
        });

        $this->syncFinancials();
    }
}
