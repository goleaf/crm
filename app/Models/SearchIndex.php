<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string                    $term
 * @property string                    $module
 * @property string                    $searchable_type
 * @property int                       $searchable_id
 * @property array<string, mixed>|null $metadata
 * @property float                     $ranking_score
 * @property int                       $search_count
 * @property \Carbon\Carbon|null       $last_searched_at
 */
final class SearchIndex extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'term',
        'module',
        'searchable_type',
        'searchable_id',
        'metadata',
        'ranking_score',
        'search_count',
        'last_searched_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'ranking_score' => 'float',
            'search_count' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Increment search count and update last searched timestamp.
     */
    public function recordSearch(): void
    {
        $this->increment('search_count');
        $this->update(['last_searched_at' => now()]);
    }

    /**
     * Update ranking score based on search frequency and recency.
     */
    public function updateRankingScore(): void
    {
        $recencyWeight = $this->last_searched_at?->diffInDays(now()) ?? 365;
        $frequencyWeight = $this->search_count;

        // Higher score for more recent and frequent searches
        $score = ($frequencyWeight * 0.7) + ((365 - min($recencyWeight, 365)) * 0.3);

        $this->update(['ranking_score' => $score]);
    }
}
