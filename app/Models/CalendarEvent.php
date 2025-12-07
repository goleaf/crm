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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property CalendarEventStatus $status
 * @property CalendarEventType $type
 * @property CalendarSyncStatus $sync_status
 */
#[ObservedBy(CalendarEventObserver::class)]
final class CalendarEvent extends Model
{
    use HasCreator;

    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

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
        'meeting_url',
        'reminder_minutes_before',
        'attendees',
        'related_id',
        'related_type',
        'sync_provider',
        'sync_status',
        'sync_external_id',
        'notes',
        'creation_source',
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
            'attendees' => 'array',
            'creation_source' => CreationSource::class,
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

        return $this->end_at->diffInMinutes($this->start_at);
    }
}
