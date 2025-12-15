<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteDiscountType;
use App\Enums\QuoteStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use App\Observers\QuoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(QuoteObserver::class)]
final class Quote extends Model
{
    use HasCreator;
    use HasFactory;
    use HasNotesAndNotables;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => QuoteStatus::DRAFT,
        'currency_code' => 'USD',
        'discount_total' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'owner_id',
        'company_id',
        'contact_id',
        'lead_id',
        'opportunity_id',
        'title',
        'description',
        'status',
        'currency_code',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'valid_until',
        'accepted_at',
        'rejected_at',
        'decision_note',
        'line_items',
        'billing_address',
        'shipping_address',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'line_items' => 'array',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'valid_until' => 'date',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
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
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return HasMany<QuoteLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuoteStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(QuoteStatusHistory::class);
    }

    /**
     * Normalize totals whenever the quote changes.
     */
    public function syncFinancials(?string $note = null): void
    {
        $lineItems = $this->lineItems()->get([
            'product_id',
            'sku',
            'name',
            'description',
            'quantity',
            'unit_price',
            'discount_type',
            'discount_value',
            'tax_rate',
            'line_total',
            'tax_total',
            'sort_order',
            'is_custom',
        ]);

        if ($lineItems->isEmpty() && is_array($this->line_items)) {
            $lineItems = collect($this->line_items)->map(function (array $item): QuoteLineItem {
                $line = new QuoteLineItem;
                $line->product_id = $item['product_id'] ?? null;
                $line->sku = $item['sku'] ?? null;
                $line->name = $item['name'] ?? 'Line item';
                $line->description = $item['description'] ?? null;
                $line->quantity = (float) ($item['quantity'] ?? 0);
                $line->unit_price = (float) ($item['unit_price'] ?? 0);
                $line->discount_type = QuoteDiscountType::tryFrom((string) ($item['discount_type'] ?? '')) ?? QuoteDiscountType::PERCENT;
                $line->discount_value = (float) ($item['discount_value'] ?? 0);
                $line->tax_rate = (float) ($item['tax_rate'] ?? 0);
                $line->line_total = (float) ($item['line_total'] ?? 0);
                $line->tax_total = (float) ($item['tax_total'] ?? 0);
                $line->sort_order = (int) ($item['sort_order'] ?? 0);
                $line->is_custom = (bool) ($item['is_custom'] ?? false);

                if ($line->line_total === 0.0) {
                    $base = round(((float) $line->quantity) * ((float) $line->unit_price), 2);
                    $discount = $line->discount_type->calculate($base, (float) $line->discount_value);
                    $line->line_total = max($base - $discount, 0);
                    $line->tax_total = round($line->line_total * ((float) $line->tax_rate / 100), 2);
                }

                return $line;
            });
        }

        $subtotal = $lineItems->sum(fn (QuoteLineItem $item): float => (float) $item->line_total);

        $discountTotal = $lineItems->sum(function (QuoteLineItem $item): float {
            $base = round(((float) $item->quantity) * ((float) $item->unit_price), 2);

            return max($base - (float) $item->line_total, 0);
        });

        $taxTotal = $lineItems->sum(function (QuoteLineItem $item): float {
            if ($item->tax_total !== null) {
                return (float) $item->tax_total;
            }

            $lineTotal = ((float) $item->quantity) * ((float) $item->unit_price);

            return round($lineTotal * ((float) ($item['tax_rate'] ?? 0) / 100), 2);
        });

        $total = max(round($subtotal + $taxTotal, 2), 0);

        $snapshot = $lineItems
            ->map(fn (QuoteLineItem $item): array => [
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_type' => $item->discount_type?->value,
                'discount_value' => (float) $item->discount_value,
                'tax_rate' => (float) $item->tax_rate,
                'line_total' => (float) $item->line_total,
                'tax_total' => (float) $item->tax_total,
                'sort_order' => (int) $item->sort_order,
                'is_custom' => (bool) $item->is_custom,
            ])
            ->values()
            ->all();

        self::withoutEvents(function () use ($subtotal, $discountTotal, $taxTotal, $total, $snapshot): void {
            $this->forceFill([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'line_items' => $snapshot,
            ])->saveQuietly();
        });
    }

    public function markAccepted(?string $note = null): void
    {
        $previous = $this->status ?? QuoteStatus::DRAFT;

        self::withoutEvents(function () use ($note): void {
            $this->forceFill([
                'status' => QuoteStatus::ACCEPTED,
                'accepted_at' => now(),
                'rejected_at' => null,
                'decision_note' => $note,
            ])->saveQuietly();
        });

        if ($previous !== QuoteStatus::ACCEPTED) {
            $this->recordStatusChange($previous, QuoteStatus::ACCEPTED, $note);
        }
    }

    public function markRejected(?string $note = null): void
    {
        $previous = $this->status ?? QuoteStatus::DRAFT;

        self::withoutEvents(function () use ($note): void {
            $this->forceFill([
                'status' => QuoteStatus::REJECTED,
                'rejected_at' => now(),
                'accepted_at' => null,
                'decision_note' => $note,
            ])->saveQuietly();
        });

        if ($previous !== QuoteStatus::REJECTED) {
            $this->recordStatusChange($previous, QuoteStatus::REJECTED, $note);
        }
    }

    public function recordStatusChange(?QuoteStatus $from, QuoteStatus $to, ?string $note = null): void
    {
        $this->statusHistories()->create([
            'team_id' => $this->team_id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => Auth::id(),
            'note' => $note,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->isPast()
            && $this->status !== QuoteStatus::ACCEPTED;
    }

    protected static function booted(): void
    {
        self::creating(function (self $quote): void {
            if (auth('web')->check()) {
                $quote->team_id ??= auth('web')->user()?->currentTeam?->getKey();
                $quote->creator_id ??= auth('web')->id();
            }

            $quote->owner_id ??= $quote->creator_id;
            $quote->status ??= QuoteStatus::DRAFT;
            $quote->currency_code ??= config('company.default_currency', 'USD');
        });

        self::saving(function (self $quote): void {
            $quote->owner_id ??= $quote->creator_id;
        });
    }
}
