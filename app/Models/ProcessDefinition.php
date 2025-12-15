<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessStatus;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ProcessStatus             $status
 * @property array<string, mixed>|null $steps
 * @property array<string, mixed>|null $business_rules
 * @property array<string, mixed>|null $event_triggers
 * @property array<string, mixed>|null $sla_config
 * @property array<string, mixed>|null $escalation_rules
 * @property array<string, mixed>|null $metadata
 */
final class ProcessDefinition extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUniqueSlug;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'name',
        'slug',
        'description',
        'status',
        'version',
        'steps',
        'business_rules',
        'event_triggers',
        'sla_config',
        'escalation_rules',
        'metadata',
        'documentation',
        'template_id',
    ];

    /**
     * Initialize trait properties to keep PHP 8.4+ composition clean.
     */
    public function __construct(array $attributes = [])
    {
        $this->constraintFields = [];

        parent::__construct($attributes);
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessStatus::class,
            'version' => 'integer',
            'steps' => 'array',
            'business_rules' => 'array',
            'event_triggers' => 'array',
            'sla_config' => 'array',
            'escalation_rules' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsTo<ProcessDefinition, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class, 'template_id');
    }

    /**
     * @return HasMany<ProcessExecution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ProcessExecution::class);
    }

    /**
     * @return HasMany<ProcessAnalytic, $this>
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ProcessAnalytic::class);
    }
}
