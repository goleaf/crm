<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\CalendarEventObserver;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Zap\Models\Schedule;

/**
 * @property CalendarEventStatus $status
 * @property CalendarEventType   $type
 * @property CalendarSyncStatus  $sync_status
 */
#[ObservedBy(CalendarEventObserver::class)]
final class CalendarEvent extends Model
{
    use HasCreator;

    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    protected static function booted(): void
    {
        self::deleted(function (self $event): void {
            resolve(\App\Services\ZapScheduleService::class)->deleteCalendarEventSchedule($event);
        });
    }

    protected function performDeleteOnModel(): void
    {
        resolve(\App\Services\ZapScheduleService::class)->deleteCalendarEventSchedule($this);

        parent::performDeleteOnModel();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'title',
        'type',
        'status',
        'is_all_day',
        'start_at',
        'end_at',
        'location',
        'room_booking',
        'meeting_url',
        'reminder_minutes_before',
        'recurrence_rule',
        'recurrence_end_date',
        'recurrence_parent_id',
        'attendees',
        'related_id',
        'related_type',
        'sync_provider',
        'sync_status',
        'sync_external_id',
        'notes',
        'agenda',
        'minutes',
        'creation_source',
        'zap_schedule_id',
        'zap_metadata',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CalendarEventStatus::SCHEDULED,
        'type' => CalendarEventType::MEETING,
        'sync_status' => CalendarSyncStatus::NOT_SYNCED,
        'is_all_day' => false,
        'attendees' => '[]',
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => CalendarEventStatus::class,
            'type' => CalendarEventType::class,
            'sync_status' => CalendarSyncStatus::class,
            'is_all_day' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'recurrence_end_date' => 'datetime',
            'attendees' => 'array',
            'creation_source' => CreationSource::class,
            'zap_metadata' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function zapSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'zap_schedule_id');
    }

    /**
     * Get the parent event for recurring instances.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<CalendarEvent, $this>
     */
    public function recurrenceParent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'recurrence_parent_id');
    }

    /**
     * Get all recurring instances of this event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CalendarEvent, $this>
     */
    public function recurrenceInstances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'recurrence_parent_id');
    }

    /**
     * @return Collection<int, array{name: string, email: string|null}>
     */
    public function attendeeCollection(): Collection
    {
        return collect($this->attendees ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && isset($item['name']))
            ->map(fn (array $attendee): array => [
                'name' => (string) $attendee['name'],
                'email' => $attendee['email'] ?? null,
            ]);
    }

    public function durationMinutes(): ?int
    {
        if ($this->end_at === null || $this->start_at === null) {
            return null;
        }

        return (int) $this->end_at->diffInMinutes($this->start_at);
    }

    /**
     * Check if this event is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->recurrence_rule !== null;
    }

    /**
     * Check if this event is a recurring instance.
     */
    public function isRecurringInstance(): bool
    {
        return $this->recurrence_parent_id !== null;
    }

    // Query Scopes for Performance

    /**
     * Scope to filter events within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function inDateRange(
        \Illuminate\Database\Eloquent\Builder $query,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereBetween('start_at', [$start, $end]);
    }

    /**
     * Scope to filter events by team.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forTeam(
        \Illuminate\Database\Eloquent\Builder $query,
        int $teamId,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter events by types.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     * @param array<string>                                        $types
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function ofTypes(
        \Illuminate\Database\Eloquent\Builder $query,
        array $types,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereIn('type', $types);
    }

    /**
     * Scope to filter events by statuses.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     * @param array<string>                                        $statuses
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function withStatuses(
        \Illuminate\Database\Eloquent\Builder $query,
        array $statuses,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Scope to search events by text.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function search(
        \Illuminate\Database\Eloquent\Builder $query,
        string $search,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($search): void {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to eager load common relationships.
     *
     * @param \Illuminate\Database\Eloquent\Builder<CalendarEvent> $query
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function withCommonRelations(
        \Illuminate\Database\Eloquent\Builder $query,
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->with(['creator:id,name', 'team:id,name']);
    }
}
