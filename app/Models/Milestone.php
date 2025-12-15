<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Observers\MilestoneObserver;
use Database\Factories\MilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int                                  $id
 * @property int                                  $team_id
 * @property int                                  $project_id
 * @property int|null                             $owner_id
 * @property string                               $title
 * @property string|null                          $description
 * @property \Illuminate\Support\Carbon            $target_date
 * @property \Illuminate\Support\Carbon|null       $actual_completion_date
 * @property MilestoneType                        $milestone_type
 * @property MilestonePriority                    $priority_level
 * @property MilestoneStatus                      $status
 * @property float                                $completion_percentage
 * @property int                                  $schedule_variance_days
 * @property bool                                 $is_critical
 * @property bool                                 $is_at_risk
 * @property int                                  $last_progress_threshold_notified
 * @property array<int, int>|null                 $reminders_sent
 * @property \Illuminate\Support\Carbon|null       $overdue_notified_at
 * @property array<int, int>|null                 $stakeholder_ids
 * @property array<int, array<string, mixed>>|null $reference_links
 * @property string|null                          $notes
 * @property bool                                 $requires_approval
 * @property \Illuminate\Support\Carbon|null       $submitted_for_approval_at
 * @property \Illuminate\Support\Carbon|null       $created_at
 * @property \Illuminate\Support\Carbon|null       $updated_at
 * @property \Illuminate\Support\Carbon|null       $deleted_at
 */
#[ObservedBy(MilestoneObserver::class)]
final class Milestone extends Model implements HasMedia
{
    /** @use HasFactory<MilestoneFactory> */
    use HasFactory;

    use HasTaxonomies;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'project_id',
        'owner_id',
        'title',
        'description',
        'target_date',
        'actual_completion_date',
        'milestone_type',
        'priority_level',
        'status',
        'completion_percentage',
        'schedule_variance_days',
        'is_critical',
        'is_at_risk',
        'last_progress_threshold_notified',
        'reminders_sent',
        'overdue_notified_at',
        'stakeholder_ids',
        'reference_links',
        'notes',
        'requires_approval',
        'submitted_for_approval_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'actual_completion_date' => 'date',
            'milestone_type' => MilestoneType::class,
            'priority_level' => MilestonePriority::class,
            'status' => MilestoneStatus::class,
            'completion_percentage' => 'decimal:2',
            'schedule_variance_days' => 'integer',
            'is_critical' => 'boolean',
            'is_at_risk' => 'boolean',
            'last_progress_threshold_notified' => 'integer',
            'reminders_sent' => 'array',
            'overdue_notified_at' => 'datetime',
            'stakeholder_ids' => 'array',
            'reference_links' => 'array',
            'requires_approval' => 'boolean',
            'submitted_for_approval_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Deliverable, $this>
     */
    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    /**
     * Dependency links where this milestone is the successor.
     *
     * @return HasMany<MilestoneDependency, $this>
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(MilestoneDependency::class, 'successor_id');
    }

    /**
     * Dependency links where this milestone is the predecessor.
     *
     * @return HasMany<MilestoneDependency, $this>
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(MilestoneDependency::class, 'predecessor_id');
    }

    /**
     * @return BelongsToMany<self, $this>
     */
    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'milestone_dependencies',
            'successor_id',
            'predecessor_id',
        )->withPivot(['dependency_type', 'lag_days', 'is_active'])->withTimestamps();
    }

    /**
     * @return BelongsToMany<self, $this>
     */
    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'milestone_dependencies',
            'predecessor_id',
            'successor_id',
        )->withPivot(['dependency_type', 'lag_days', 'is_active'])->withTimestamps();
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'milestone_task')
            ->withPivot(['weight'])
            ->withTimestamps();
    }

    /**
     * Strategic goals are taxonomies with type "goal".
     *
     * @return MorphToMany<Taxonomy, $this>
     */
    public function goals(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'goal')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return HasMany<MilestoneApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(MilestoneApproval::class);
    }

    /**
     * @return HasMany<MilestoneProgressSnapshot, $this>
     */
    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(MilestoneProgressSnapshot::class)->latest();
    }

    public function isCompleted(): bool
    {
        return $this->status === MilestoneStatus::COMPLETED;
    }

    /**
     * @return Collection<int, User>
     */
    public function notificationRecipients(): Collection
    {
        $recipients = collect();

        if ($this->owner instanceof User) {
            $recipients->push($this->owner);
        }

        $stakeholderIds = $this->stakeholder_ids ?? [];

        if ($stakeholderIds !== []) {
            $recipients = $recipients->merge(User::query()->whereIn('id', $stakeholderIds)->get());
        }

        return $recipients
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->values();
    }
}

