<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Activity extends Model
{
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'event',
        'changes',
        'causer_id',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * Adapter for filament-activity-log expectations (Spatie-compatible).
     */
    public function getChangesAttribute($value = null): Collection
    {
        $changes = $value ?? $this->getAttributes()['changes'] ?? [];

        return collect($changes ?? []);
    }

    /**
     * Provide a Spatie-style properties attribute mapped to our changes payload.
     *
     * @return array<string, mixed>
     */
    public function getPropertiesAttribute(): array
    {
        $raw = $this->getAttributes()['changes'] ?? [];

        return $raw instanceof Collection ? $raw->toArray() : ($raw ?? []);
    }
}
