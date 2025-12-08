<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessEventType;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProcessEventType $event_type
 * @property array<string, mixed>|null $event_data
 * @property array<string, mixed>|null $state_before
 * @property array<string, mixed>|null $state_after
 */
final class ProcessAuditLog extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'execution_id',
        'execution_step_id',
        'user_id',
        'event_type',
        'event_description',
        'event_data',
        'state_before',
        'state_after',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => ProcessEventType::class,
            'event_data' => 'array',
            'state_before' => 'array',
            'state_after' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProcessExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(ProcessExecution::class, 'execution_id');
    }

    /**
     * @return BelongsTo<ProcessExecutionStep, $this>
     */
    public function executionStep(): BelongsTo
    {
        return $this->belongsTo(ProcessExecutionStep::class, 'execution_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
