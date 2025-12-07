<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceReminderType;
use App\Models\Concerns\HasTeam;
use App\Observers\InvoiceReminderObserver;
use Database\Factories\InvoiceReminderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property InvoiceReminderType $reminder_type
 * @property Carbon $remind_at
 * @property Carbon|null $sent_at
 */
#[ObservedBy(InvoiceReminderObserver::class)]
final class InvoiceReminder extends Model
{
    /** @use HasFactory<InvoiceReminderFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'team_id',
        'reminder_type',
        'remind_at',
        'sent_at',
        'channel',
        'notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'reminder_type' => InvoiceReminderType::class,
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
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
