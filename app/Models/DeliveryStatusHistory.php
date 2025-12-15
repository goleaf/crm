<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\DeliveryStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DeliveryStatusHistory extends Model
{
    /** @use HasFactory<DeliveryStatusHistoryFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_id',
        'team_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => DeliveryStatus::class,
            'to_status' => DeliveryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected static function booted(): void
    {
        self::creating(function (self $history): void {
            if ($history->team_id === null && $history->delivery !== null) {
                $history->team_id = $history->delivery->team_id;
            }

            $history->changed_by ??= auth('web')->id();
        });
    }
}
