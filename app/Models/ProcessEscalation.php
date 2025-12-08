<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $resolved_at
 * @property array<string, mixed>|null $escalation_config
 */
final class ProcessEscalation extends Model
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
        'escalated_to_id',
        'escalated_by_id',
        'escalation_reason',
        'escalation_notes',
        'escalation_config',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'escalation_config' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
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
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_id');
    }
}
