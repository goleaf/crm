<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessApprovalStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProcessApprovalStatus $status
 */
final class PurchaseOrderApproval extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'team_id',
        'requested_by_id',
        'approver_id',
        'status',
        'due_at',
        'decided_at',
        'approval_notes',
        'decision_notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessApprovalStatus::class,
            'due_at' => 'datetime',
            'decided_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $approval): void {
            if ($approval->purchase_order_id !== null) {
                $approval->team_id = $approval->purchaseOrder()->withoutTrashed()->value('team_id') ?? $approval->team_id;
            }

            if ($approval->requested_by_id === null && auth('web')->check()) {
                $approval->requested_by_id = auth('web')->id();
            }
        });

        $refresh = static function (self $approval): void {
            $approval->purchaseOrder()->withoutTrashed()->first()?->syncApprovalState();
        };

        self::saved($refresh);
        self::deleted($refresh);
    }
}
