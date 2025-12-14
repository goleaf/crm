<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string                    $term
 * @property string|null               $module
 * @property int                       $frequency
 * @property float                     $relevance_score
 * @property array<string, mixed>|null $metadata
 * @property bool                      $is_active
 */
final class SearchSuggestion extends Model
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
        'frequency',
        'relevance_score',
        'metadata',
        'is_active',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => 'integer',
            'relevance_score' => 'float',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Increment frequency and update relevance score.
     */
    public function recordUsage(): void
    {
        $this->increment('frequency');
        $this->updateRelevanceScore();
    }

    /**
     * Update relevance score based on frequency and other factors.
     */
    public function updateRelevanceScore(): void
    {
        // Simple scoring based on frequency
        $score = min($this->frequency * 0.1, 10.0);
        $this->update(['relevance_score' => $score]);
    }
}
