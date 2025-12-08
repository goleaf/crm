<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TerritoryQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'territory_id',
        'period',
        'revenue_target',
        'unit_target',
        'revenue_actual',
        'unit_actual',
    ];

    protected $casts = [
        'revenue_target' => 'decimal:2',
        'unit_target' => 'integer',
        'revenue_actual' => 'decimal:2',
        'unit_actual' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * Calculate revenue attainment percentage
     */
    public function revenueAttainment(): ?float
    {
        if (! $this->revenue_target || $this->revenue_target === 0) {
            return null;
        }

        return ($this->revenue_actual / $this->revenue_target) * 100;
    }

    /**
     * Calculate unit attainment percentage
     */
    public function unitAttainment(): ?float
    {
        if (! $this->unit_target || $this->unit_target === 0) {
            return null;
        }

        return ($this->unit_actual / $this->unit_target) * 100;
    }
}
