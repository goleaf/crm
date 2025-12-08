<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use App\Observers\KnowledgeTagObserver;
use Database\Factories\KnowledgeTagFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(KnowledgeTagObserver::class)]
final class KnowledgeTag extends Model
{
    use HasCreator;

    /** @use HasFactory<KnowledgeTagFactory> */
    use HasFactory;

    use HasTeam;
    use HasUniqueSlug;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Initialize trait properties to keep PHP 8.4+ composition clean.
     */
    public function __construct(array $attributes = [])
    {
        $this->reslugOnBaseChange = true;

        parent::__construct($attributes);
    }

    /**
     * @return BelongsToMany<KnowledgeArticle, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'knowledge_article_tag', 'tag_id', 'article_id')->withTimestamps();
    }
}
