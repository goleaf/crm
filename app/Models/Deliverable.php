<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliverableStatus;
use App\Observers\DeliverableObserver;
use Database\Factories\DeliverableFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                            $id
 * @property int                            $milestone_id
 * @property int|null                       $owner_id
 * @property string                         $name
 * @property string|null                    $description
 * @property \Illuminate\Support\Carbon      $due_date
 * @property string|null                    $acceptance_criteria
 * @property DeliverableStatus              $status
 * @property string|null                    $completion_evidence_url
 * @property string|null                    $completion_evidence_path
 * @property bool                           $requires_approval
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
#[ObservedBy(DeliverableObserver::class)]
final class Deliverable extends Model
{
    /** @use HasFactory<DeliverableFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'milestone_id',
        'owner_id',
        'name',
        'description',
        'due_date',
        'acceptance_criteria',
        'status',
        'completion_evidence_url',
        'completion_evidence_path',
        'requires_approval',
        'completed_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => DeliverableStatus::class,
            'requires_approval' => 'boolean',
            'completed_at' => 'datetime',
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
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function hasCompletionEvidence(): bool
    {
        return filled($this->completion_evidence_url) || filled($this->completion_evidence_path);
    }
}

