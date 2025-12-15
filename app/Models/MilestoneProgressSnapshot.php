<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MilestoneProgressSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                            $id
 * @property int                            $milestone_id
 * @property float                          $completion_percentage
 * @property int                            $schedule_variance_days
 * @property int                            $remaining_tasks_count
 * @property int                            $blocked_tasks_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class MilestoneProgressSnapshot extends Model
{
    /** @use HasFactory<MilestoneProgressSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'milestone_id',
        'completion_percentage',
        'schedule_variance_days',
        'remaining_tasks_count',
        'blocked_tasks_count',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'completion_percentage' => 'decimal:2',
            'schedule_variance_days' => 'integer',
            'remaining_tasks_count' => 'integer',
            'blocked_tasks_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }
}

