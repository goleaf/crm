<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TerritoryOverlapResolution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TerritoryOverlap extends Model
{
    use HasFactory;

    protected $fillable = [
        'territory_a_id',
        'territory_b_id',
        'resolution_strategy',
        'priority_territory_id',
        'notes',
    ];

    protected $casts = [
        'resolution_strategy' => TerritoryOverlapResolution::class,
        'priority_territory_id' => 'integer',
    ];

    public function territoryA(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_a_id');
    }

    public function territoryB(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_b_id');
    }

    public function priorityTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'priority_territory_id');
    }
}
