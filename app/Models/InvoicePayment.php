<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoicePaymentStatus;
use App\Models\Concerns\HasTeam;
use App\Observers\InvoicePaymentObserver;
use Database\Factories\InvoicePaymentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property float                $amount
 * @property InvoicePaymentStatus $status
 * @property Carbon|null          $paid_at
 */
#[ObservedBy(InvoicePaymentObserver::class)]
final class InvoicePayment extends Model
{
    /** @use HasFactory<InvoicePaymentFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'team_id',
        'amount',
        'currency_code',
        'paid_at',
        'method',
        'reference',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'status' => InvoicePaymentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
