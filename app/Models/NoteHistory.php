<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NoteHistoryEvent;
use App\Enums\NoteVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property NoteVisibility   $visibility
 * @property NoteHistoryEvent $event
 */
final class NoteHistory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'note_id',
        'team_id',
        'user_id',
        'title',
        'category',
        'visibility',
        'body',
        'event',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => NoteVisibility::class,
            'event' => NoteHistoryEvent::class,
        ];
    }

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Compare history snapshot with provided data to avoid duplicates.
     *
     * @param array<string, mixed> $snapshot
     */
    public function matchesSnapshot(array $snapshot): bool
    {
        return $this->title === ($snapshot['title'] ?? null)
            && $this->category === ($snapshot['category'] ?? null)
            && $this->visibility?->value === ($snapshot['visibility'] ?? null)
            && trim((string) $this->body) === trim((string) ($snapshot['body'] ?? ''));
    }
}
