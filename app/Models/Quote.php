<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Quote extends Model
{
    use HasCreator;
    use HasNotes;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'company_id',
        'contact_id',
        'opportunity_id',
        'title',
        'status',
        'currency_code',
        'subtotal',
        'tax_total',
        'total',
        'valid_until',
        'accepted_at',
        'rejected_at',
        'decision_note',
        'line_items',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'line_items' => 'array',
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
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Normalize totals whenever the quote changes.
     */
    public function syncTotals(): void
    {
        $items = collect($this->line_items ?? []);

        $subtotal = $items->sum(fn (array $item): float => round(((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2));
        $taxTotal = $items->sum(function (array $item): float {
            $lineTotal = ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0));

            return round($lineTotal * ((float) ($item['tax_rate'] ?? 0) / 100), 2);
        });

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => max(round($subtotal + $taxTotal, 2), 0),
        ]);
    }

    public function markAccepted(?string $note = null): void
    {
        $this->forceFill([
            'status' => QuoteStatus::ACCEPTED,
            'accepted_at' => now(),
            'decision_note' => $note,
        ])->save();
    }

    public function markRejected(?string $note = null): void
    {
        $this->forceFill([
            'status' => QuoteStatus::REJECTED,
            'rejected_at' => now(),
            'decision_note' => $note,
        ])->save();
    }

    protected static function booted(): void
    {
        self::creating(function (self $quote): void {
            if (auth('web')->check()) {
                $quote->team_id ??= auth('web')->user()?->currentTeam?->getKey();
                $quote->creator_id ??= auth('web')->id();
            }
        });

        self::saving(function (self $quote): void {
            $quote->syncTotals();
        });
    }
}
