<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $version
 */
final class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'team_id',
        'uploaded_by',
        'version',
        'file_path',
        'disk',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected static function booted(): void
    {
        self::creating(function (self $version): void {
            if ($version->team_id === null && $version->document !== null) {
                $version->team_id = $version->document->team_id;
            }

            if ($version->uploaded_by === null && auth('web')->check()) {
                $version->uploaded_by = auth('web')->id();
            }

            $next = $version->document?->versions()->max('version') ?? 0;
            $version->version = $next + 1;
        });

        self::created(function (self $version): void {
            $version->document?->forceFill(['current_version_id' => $version->getKey()])->saveQuietly();
        });
    }
}
