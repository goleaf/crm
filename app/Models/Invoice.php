<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceRecurrenceFrequency;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasReferenceNumbering;
use App\Models\Concerns\HasTeam;
use App\Observers\InvoiceObserver;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use MohamedSaid\Referenceable\Traits\HasReference;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int           $id
 * @property int           $team_id
 * @property string        $number
 * @property int           $sequence
 * @property InvoiceStatus $status
 * @property Carbon|null   $issue_date
 * @property Carbon|null   $due_date
 * @property float         $subtotal
 * @property float         $tax_total
 * @property float         $discount_total
 * @property float         $late_fee_amount
 * @property float         $total
 * @property float         $balance_due
 * @property Carbon|null   $late_fee_applied_at
 * @property Carbon|null   $sent_at
 * @property Carbon|null   $paid_at
 * @property Carbon|null   $created_at
 * @property Carbon|null   $updated_at
 */
#[ObservedBy(InvoiceObserver::class)]
final class Invoice extends Model implements HasMedia
{
    use HasCreator;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use HasReference;
    use HasReferenceNumbering;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;

    protected string $referenceColumn = 'number';

    protected string $referenceStrategy = 'template';

    /**
     * @var array{format: string, sequence_length: int}
     */
    protected array $referenceTemplate = [
        'format' => 'INV-{YEAR}-{SEQ}',
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
        'order_id',
        'parent_invoice_id',
        'sequence',
        'number',
        'status',
        'issue_date',
        'due_date',
        'payment_terms',
        'currency_code',
        'fx_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'late_fee_rate',
        'late_fee_amount',
        'late_fee_applied_at',
        'total',
        'balance_due',
        'template_key',
        'reminder_policy',
        'sent_at',
        'paid_at',
        'last_reminded_at',
        'is_recurring_template',
        'recurring_frequency',
        'recurring_interval',
        'recurring_starts_at',
        'recurring_ends_at',
        'next_issue_at',
        'notes',
        'terms',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'status' => InvoiceStatus::DRAFT,
        'currency_code' => 'USD',
        'fx_rate' => 1,
        'discount_total' => 0,
        'late_fee_rate' => 0,
    ];

    /**
     * Reference generation is coordinated through registerNumberIfMissing to seed counters.
     */
    protected static function bootHasReference(): void
    {
        // Override trait boot hook to avoid premature generation.
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'recurring_starts_at' => 'date',
            'recurring_ends_at' => 'date',
            'next_issue_at' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'late_fee_applied_at' => 'datetime',
            'status' => InvoiceStatus::class,
            'recurring_frequency' => InvoiceRecurrenceFrequency::class,
            'is_recurring_template' => 'boolean',
            'creation_source' => CreationSource::class,
            'fx_rate' => 'decimal:6',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'late_fee_rate' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'balance_due' => 'decimal:2',
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Invoice, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_invoice_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InvoiceLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InvoicePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InvoiceReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InvoiceStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(InvoiceStatusHistory::class);
    }

    public function registerNumberIfMissing(): void
    {
        if ($this->team_id === null) {
            return;
        }

        $this->primeReferenceCounter('issue_date');

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
     * Recalculate totals, balance, and status to keep the invoice ledger consistent.
     */
    public function syncFinancials(?string $note = null): void
    {
        $lineItems = $this->lineItems()->get(['quantity', 'unit_price', 'tax_rate']);

        $subtotal = $lineItems->sum(fn (InvoiceLineItem $item): float => round(((float) $item->quantity) * ((float) $item->unit_price), 2));

        $taxTotal = $lineItems->sum(function (InvoiceLineItem $item): float {
            $lineTotal = ((float) $item->quantity) * ((float) $item->unit_price);

            return round($lineTotal * ((float) $item->tax_rate / 100), 2);
        });
        $discountTotal = (float) $this->discount_total;
        $completedPaymentsTotal = (float) $this->payments()
            ->where('status', InvoicePaymentStatus::COMPLETED)
            ->sum('amount');

        $lateFee = $this->calculateLateFee($subtotal, $discountTotal, $taxTotal, $completedPaymentsTotal);
        $total = max(round($subtotal - $discountTotal + $taxTotal + $lateFee, 2), 0);
        $balance = max(round($total - $completedPaymentsTotal, 2), 0);

        $previousStatus = $this->status ?? InvoiceStatus::DRAFT;
        $nextStatus = $this->determineStatus($total, $completedPaymentsTotal, $balance);

        self::withoutEvents(function () use ($subtotal, $taxTotal, $discountTotal, $lateFee, $total, $balance, $nextStatus): void {
            $this->forceFill([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'late_fee_amount' => $lateFee,
                'late_fee_applied_at' => $this->late_fee_applied_at,
                'total' => $total,
                'balance_due' => $balance,
                'status' => $nextStatus,
                'paid_at' => $nextStatus === InvoiceStatus::PAID ? ($this->paid_at ?? Date::now()) : $this->paid_at,
            ])->saveQuietly();
        });

        if ($previousStatus !== $nextStatus) {
            $this->recordStatusChange($previousStatus, $nextStatus, $note);
        }

        $this->order?->syncFinancials();
    }

    private function determineStatus(float $total, float $paid, float $balance): InvoiceStatus
    {
        if ($this->status === InvoiceStatus::CANCELLED) {
            return InvoiceStatus::CANCELLED;
        }

        if ($total > 0 && $paid >= $total) {
            return InvoiceStatus::PAID;
        }

        if ($paid > 0 && $balance > 0) {
            return InvoiceStatus::PARTIAL;
        }

        if ($this->isOverdue()) {
            return InvoiceStatus::OVERDUE;
        }

        if ($this->status === InvoiceStatus::SENT || $this->sent_at !== null) {
            return InvoiceStatus::SENT;
        }

        return $this->status ?? InvoiceStatus::DRAFT;
    }

    private function calculateLateFee(float $subtotal, float $discount, float $taxTotal, float $paid): float
    {
        if ($this->late_fee_rate <= 0) {
            return (float) $this->late_fee_amount;
        }

        if (! $this->isOverdue()) {
            return (float) $this->late_fee_amount;
        }

        if ($this->late_fee_applied_at !== null) {
            return (float) $this->late_fee_amount;
        }

        $base = max($subtotal - $discount + $taxTotal - $paid, 0);
        $fee = round($base * ((float) $this->late_fee_rate / 100), 2);
        $this->late_fee_applied_at = Date::now();

        return $fee;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->status !== InvoiceStatus::PAID
            && $this->due_date->lt(Date::now());
    }

    public function markSent(): void
    {
        $previousStatus = $this->status;

        self::withoutEvents(function (): void {
            $this->forceFill([
                'sent_at' => Date::now(),
                'status' => InvoiceStatus::SENT,
            ])->saveQuietly();
        });

        if ($previousStatus !== InvoiceStatus::SENT) {
            $this->recordStatusChange($previousStatus, InvoiceStatus::SENT);
        }
    }

    public function recordStatusChange(?InvoiceStatus $from, InvoiceStatus $to, ?string $note = null): void
    {
        $this->statusHistories()->create([
            'team_id' => $this->team_id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => auth('web')->id(),
            'note' => $note,
        ]);
    }

    protected static function booted(): void
    {
        self::creating(function (self $invoice): void {
            if ($invoice->team_id === null && auth('web')->check()) {
                $invoice->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            if ($invoice->creator_id === null && auth('web')->check()) {
                $invoice->creator_id = auth('web')->id();
            }

            $invoice->issue_date ??= Date::now();
            $invoice->creation_source ??= CreationSource::WEB;
            $invoice->currency_code ??= config('company.default_currency', 'USD');
            $invoice->registerNumberIfMissing();
        });

        self::saving(function (self $invoice): void {
            $invoice->registerNumberIfMissing();
        });
    }
}
