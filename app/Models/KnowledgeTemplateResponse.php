<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\KnowledgeTemplateResponseObserver;
use Database\Factories\KnowledgeTemplateResponseFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(KnowledgeTemplateResponseObserver::class)]
final class KnowledgeTemplateResponse extends Model
{
    use HasCreator;

    /** @use HasFactory<KnowledgeTemplateResponseFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'visibility',
        'is_active',
        'category_id',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => ArticleVisibility::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KnowledgeCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }
}
