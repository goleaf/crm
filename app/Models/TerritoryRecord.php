<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class TerritoryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'territory_id',
        'record_type',
        'record_id',
        'is_primary',
        'assigned_at',
        'assignment_reason',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function record(): MorphTo
    {
        return $this->morphTo();
    }
}
