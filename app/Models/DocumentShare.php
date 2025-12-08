<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\DocumentShareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

final class DocumentShare extends Model
{
    /** @use HasFactory<DocumentShareFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'team_id',
        'user_id',
        'permission',
    ];

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        self::creating(function (self $share): void {
            if ($share->team_id === null && $share->document !== null) {
                $share->team_id = $share->document->team_id;
            }

            if ($share->team_id === null && Auth::check()) {
                $share->team_id = Auth::user()?->currentTeam?->getKey();
            }
        });
    }
}
