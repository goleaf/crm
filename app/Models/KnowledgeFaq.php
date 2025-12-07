<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Knowledge\ArticleVisibility;
use App\Enums\Knowledge\FaqStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\KnowledgeFaqObserver;
use Database\Factories\KnowledgeFaqFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(KnowledgeFaqObserver::class)]
final class KnowledgeFaq extends Model
{
    use HasCreator;

    /** @use HasFactory<KnowledgeFaqFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question',
        'answer',
        'status',
        'visibility',
        'position',
        'article_id',
        'creator_id',
        'team_id',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => FaqStatus::class,
            'visibility' => ArticleVisibility::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KnowledgeArticle, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'article_id');
    }
}
