<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use App\Observers\KnowledgeArticleObserver;
use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Database\Factories\KnowledgeArticleFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property Carbon|null $published_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $review_due_at
 * @property ArticleStatus $status
 * @property ArticleVisibility $visibility
 */
#[ObservedBy(KnowledgeArticleObserver::class)]
final class KnowledgeArticle extends Model implements HasMedia, HasReaction
{
    use HasCreator;

    /** @use HasFactory<KnowledgeArticleFactory> */
    use HasFactory;

    use HasTaxonomies;
    use HasTeam;
    use HasUniqueSlug;
    use InteractsWithMedia;
    use Reactable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'status',
        'visibility',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'allow_comments',
        'allow_ratings',
        'is_featured',
        'published_at',
        'archived_at',
        'review_due_at',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'approval_notes',
        'category_id',
        'team_id',
        'creator_id',
        'author_id',
        'approver_id',
        'current_version_id',
    ];

    protected string $uniqueBaseField = 'title';

    protected string $uniqueSuffixFormat = '-{n}';

    protected bool $reslugOnBaseChange = true;

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'visibility' => ArticleVisibility::class,
            'allow_comments' => 'boolean',
            'allow_ratings' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'review_due_at' => 'datetime',
            'meta_keywords' => 'array',
            'view_count' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KnowledgeCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * @return BelongsTo<KnowledgeArticleVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticleVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<KnowledgeArticleVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class, 'article_id')->latest('version');
    }

    /**
     * @return BelongsToMany<KnowledgeTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_article_tag', 'article_id', 'tag_id')->withTimestamps();
    }

    /**
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taxonomyCategories(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'knowledge_category')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taxonomyTags(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'knowledge_tag')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return HasMany<KnowledgeArticleRelation, $this>
     */
    public function articleRelations(): HasMany
    {
        return $this->hasMany(KnowledgeArticleRelation::class, 'article_id');
    }

    /**
     * @return HasMany<KnowledgeArticleApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(KnowledgeArticleApproval::class, 'article_id');
    }

    /**
     * @return HasMany<KnowledgeArticleComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(KnowledgeArticleComment::class, 'article_id');
    }

    /**
     * @return HasMany<KnowledgeArticleRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(KnowledgeArticleRating::class, 'article_id');
    }

    /**
     * @return BelongsToMany<self, $this>
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'knowledge_article_relations',
            'article_id',
            'related_article_id'
        )->withPivot(['relation_type', 'team_id'])->withTimestamps();
    }

    /**
     * @return BelongsToMany<self, $this>
     */
    public function relatedByArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'knowledge_article_relations',
            'related_article_id',
            'article_id'
        )->withPivot(['relation_type', 'team_id'])->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('attachments')
            ->useDisk(config('filament.default_filesystem_disk', 'public'))
            ->useFallbackUrl('')
            ->useFallbackPath('');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
