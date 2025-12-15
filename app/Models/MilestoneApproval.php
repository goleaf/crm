<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MilestoneApprovalStatus;
use App\Observers\MilestoneApprovalObserver;
use Database\Factories\MilestoneApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                            $id
 * @property int                            $milestone_id
 * @property int                            $step_order
 * @property int|null                       $approver_id
 * @property string|null                    $approval_criteria
 * @property MilestoneApprovalStatus        $status
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property string|null                    $decision_comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[ObservedBy(MilestoneApprovalObserver::class)]
final class MilestoneApproval extends Model
{
    /** @use HasFactory<MilestoneApprovalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'milestone_id',
        'step_order',
        'approver_id',
        'approval_criteria',
        'status',
        'requested_at',
        'decided_at',
        'decision_comment',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'status' => MilestoneApprovalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}

