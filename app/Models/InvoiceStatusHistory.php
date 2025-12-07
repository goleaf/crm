<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\InvoiceStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InvoiceStatus|null $from_status
 * @property InvoiceStatus $to_status
 */
final class InvoiceStatusHistory extends Model
{
    /** @use HasFactory<InvoiceStatusHistoryFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'team_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    /**
     * @return array<string, string|class-string|null>
     */
    protected function casts(): array
    {
        return [
            'from_status' => InvoiceStatus::class,
            'to_status' => InvoiceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
