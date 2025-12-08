<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 */
final class Document extends Model
{
    use HasCreator;

    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use HasTaxonomies;
    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'template_id',
        'current_version_id',
        'title',
        'description',
        'visibility',
    ];

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /**
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taxonomyCategories(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'document_category')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taxonomyTags(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'document_tag')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\DocumentShare, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }
}
