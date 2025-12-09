<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtensionStatus;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ExtensionType             $type
 * @property ExtensionStatus           $status
 * @property HookEvent|null            $target_event
 * @property array<string, mixed>|null $configuration
 * @property array<string, mixed>|null $permissions
 * @property array<string, mixed>|null $metadata
 */
final class Extension extends Model
{
    use HasFactory;
    use HasTeam;
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
        'type',
        'status',
        'version',
        'priority',
        'target_model',
        'target_event',
        'handler_class',
        'handler_method',
        'configuration',
        'permissions',
        'metadata',
        'execution_count',
        'failure_count',
        'last_executed_at',
        'last_error',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => ExtensionType::class,
            'status' => ExtensionStatus::class,
            'target_event' => HookEvent::class,
            'priority' => 'integer',
            'configuration' => 'array',
            'permissions' => 'array',
            'metadata' => 'array',
            'execution_count' => 'integer',
            'failure_count' => 'integer',
            'last_executed_at' => 'datetime',
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
     * @return HasMany<ExtensionExecution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ExtensionExecution::class);
    }

    /**
     * Check if extension is active.
     */
    public function isActive(): bool
    {
        return $this->status === ExtensionStatus::ACTIVE;
    }

    /**
     * Increment execution count.
     */
    public function incrementExecutionCount(): void
    {
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);
    }

    /**
     * Increment failure count.
     */
    public function incrementFailureCount(?string $error = null): void
    {
        $this->increment('failure_count');
        if ($error) {
            $this->update(['last_error' => $error]);
        }
    }
}
