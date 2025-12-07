<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessApprovalStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ProcessApprovalStatus $status
 * @property Carbon|null $due_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $escalated_at
 */
final class ProcessApproval extends Model
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
        'requested_by_id',
        'approver_id',
        'status',
        'approval_notes',
        'decision_notes',
        'due_at',
        'decided_at',
        'escalated_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProcessApprovalStatus::class,
            'due_at' => 'datetime',
            'decided_at' => 'datetime',
            'escalated_at' => 'datetime',
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
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
