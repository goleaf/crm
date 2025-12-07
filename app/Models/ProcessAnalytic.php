<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $metric_date
 */
final class ProcessAnalytic extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'process_definition_id',
        'metric_date',
        'executions_started',
        'executions_completed',
        'executions_failed',
        'sla_breaches',
        'escalations',
        'avg_completion_time_seconds',
        'min_completion_time_seconds',
        'max_completion_time_seconds',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'executions_started' => 'integer',
            'executions_completed' => 'integer',
            'executions_failed' => 'integer',
            'sla_breaches' => 'integer',
            'escalations' => 'integer',
            'avg_completion_time_seconds' => 'integer',
            'min_completion_time_seconds' => 'integer',
            'max_completion_time_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProcessDefinition, $this>
     */
    public function processDefinition(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class);
    }
}
