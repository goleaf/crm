<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class InventoryAdjustment extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'adjustable_type',
        'adjustable_id',
        'user_id',
        'quantity_before',
        'quantity_after',
        'adjustment_quantity',
        'reason',
        'notes',
        'reference_type',
        'reference_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'adjustment_quantity' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function adjustable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        self::creating(function (self $adjustment): void {
            if ($adjustment->team_id === null && auth('web')->check()) {
                $adjustment->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            if ($adjustment->user_id === null && auth('web')->check()) {
                $adjustment->user_id = auth('web')->id();
            }
        });
    }
}
