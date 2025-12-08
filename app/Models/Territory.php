<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TerritoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Territory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'code',
        'type',
        'description',
        'parent_id',
        'level',
        'path',
        'assignment_rules',
        'revenue_quota',
        'unit_quota',
        'quota_period',
        'is_active',
    ];

    protected $casts = [
        'type' => TerritoryType::class,
        'assignment_rules' => 'array',
        'revenue_quota' => 'decimal:2',
        'unit_quota' => 'integer',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Territory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Territory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Territory::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TerritoryAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(TerritoryRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryQuota, $this>
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(TerritoryQuota::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryTransfer, $this>
     */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(TerritoryTransfer::class, 'from_territory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryTransfer, $this>
     */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(TerritoryTransfer::class, 'to_territory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryOverlap, $this>
     */
    public function overlapsA(): HasMany
    {
        return $this->hasMany(TerritoryOverlap::class, 'territory_a_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TerritoryOverlap, $this>
     */
    public function overlapsB(): HasMany
    {
        return $this->hasMany(TerritoryOverlap::class, 'territory_b_id');
    }

    /**
     * Get all ancestors of this territory
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants of this territory
     */
    public function descendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->descendants());
        }

        return $descendants;
    }

    /**
     * Update the materialized path
     */
    public function updatePath(): void
    {
        $path = $this->parent ? $this->parent->path.'/'.$this->id : (string) $this->id;
        $this->update(['path' => $path]);
    }

    /**
     * Check if a record matches the assignment rules
     */
    public function matchesAssignmentRules(Model $record): bool
    {
        if (empty($this->assignment_rules)) {
            return false;
        }

        foreach ($this->assignment_rules as $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? '=';
            $value = $rule['value'] ?? null;
            if (! $field) {
                continue;
            }
            if (! isset($record->$field)) {
                continue;
            }

            $recordValue = $record->$field;

            $matches = match ($operator) {
                '=' => $recordValue === $value,
                '!=' => $recordValue !== $value,
                '>' => $recordValue > $value,
                '<' => $recordValue < $value,
                '>=' => $recordValue >= $value,
                '<=' => $recordValue <= $value,
                'contains' => str_contains((string) $recordValue, (string) $value),
                'starts_with' => str_starts_with((string) $recordValue, (string) $value),
                'ends_with' => str_ends_with((string) $recordValue, (string) $value),
                'in' => in_array($recordValue, (array) $value),
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }
}
