<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

final class Activity extends Model
{
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'subject_type',
        'subject_id',
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
    protected function getChangesAttribute($value = null): Collection
    {
        $raw = $value ?? $this->getAttributes()['changes'] ?? [];

        // Handle JSON string from database
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $changes = is_array($decoded) ? $decoded : [];
        } else {
            $changes = is_array($raw) ? $raw : [];
        }

        return collect($changes);
    }

    /**
     * Provide a Spatie-style properties attribute mapped to our changes payload.
     *
     * @return array<string, mixed>
     */
    protected function getPropertiesAttribute(): array
    {
        $raw = $this->getAttributes()['changes'] ?? [];

        // Handle JSON string from database
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $raw instanceof Collection ? $raw->toArray() : (is_array($raw) ? $raw : []);
    }
}
