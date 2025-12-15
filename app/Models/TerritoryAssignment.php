<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TerritoryRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TerritoryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'territory_id',
        'user_id',
        'role',
        'is_primary',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'role' => TerritoryRole::class,
        'is_primary' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the assignment is currently active
     */
    public function isActive(): bool
    {
        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        return ! ($this->end_date && $now->gt($this->end_date));
    }
}
