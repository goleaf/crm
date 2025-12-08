<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Observers\KnowledgeArticleRelationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(KnowledgeArticleRelationObserver::class)]
final class KnowledgeArticleRelation extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'related_article_id',
        'team_id',
        'relation_type',
    ];

    /**
     * @return BelongsTo<KnowledgeArticle, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'article_id');
    }

    /**
     * @return BelongsTo<KnowledgeArticle, $this>
     */
    public function relatedArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'related_article_id');
    }
}
