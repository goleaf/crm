<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessExecutionStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ProcessExecutionStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $sla_due_at
 * @property array<string, mixed>|null $context_data
 * @property array<string, mixed>|null $execution_state
 * @property array<string, mixed>|null $rollback_data
 */
final class ProcessExecution extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'process_definition_id',
        'initiated_by_id',
        'status',
        'process_version',
        'context_data',
        'execution_state',
        'started_at',
        'completed_at',
        'sla_due_at',
        'error_message',
        'rollback_data',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessExecutionStatus::class,
            'process_version' => 'integer',
            'context_data' => 'array',
            'execution_state' => 'array',
            'rollback_data' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'sla_due_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProcessDefinition, $this>
     */
    public function processDefinition(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    /**
     * @return HasMany<ProcessExecutionStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ProcessExecutionStep::class, 'execution_id');
    }

    /**
     * @return HasMany<ProcessApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ProcessApproval::class, 'execution_id');
    }

    /**
     * @return HasMany<ProcessEscalation, $this>
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(ProcessEscalation::class, 'execution_id');
    }

    /**
     * @return HasMany<ProcessAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ProcessAuditLog::class, 'execution_id');
    }
}
