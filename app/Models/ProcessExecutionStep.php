<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessStepStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ProcessStepStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $due_at
 * @property array<string, mixed>|null $step_config
 * @property array<string, mixed>|null $input_data
 * @property array<string, mixed>|null $output_data
 */
final class ProcessExecutionStep extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'execution_id',
        'team_id',
        'assigned_to_id',
        'step_key',
        'step_name',
        'step_order',
        'status',
        'step_config',
        'input_data',
        'output_data',
        'started_at',
        'completed_at',
        'due_at',
        'error_message',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessStepStatus::class,
            'step_order' => 'integer',
            'step_config' => 'array',
            'input_data' => 'array',
            'output_data' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return HasMany<ProcessApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ProcessApproval::class, 'execution_step_id');
    }

    /**
     * @return HasMany<ProcessEscalation, $this>
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(ProcessEscalation::class, 'execution_step_id');
    }

    /**
     * @return HasMany<ProcessAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ProcessAuditLog::class, 'execution_step_id');
    }
}
