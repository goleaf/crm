<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class TerritoryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_territory_id',
        'to_territory_id',
        'record_type',
        'record_id',
        'initiated_by',
        'reason',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function fromTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'from_territory_id');
    }

    public function toTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'to_territory_id');
    }

    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
