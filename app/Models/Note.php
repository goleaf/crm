<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\NoteObserver;
use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Facades\Entities;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(NoteObserver::class)]
final class Note extends Model implements HasCustomFields, HasMedia, HasReaction
{
    use HasCreator;

    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    use HasTeam;
    use InteractsWithMedia;
    use Reactable;
    use SoftDeletes;
    use UsesCustomFields;

    protected $fillable = [
        'team_id',
        'creator_id',
        'title',
        'category',
        'visibility',
        'is_template',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB->value,
        'category' => NoteCategory::GENERAL->value,
        'visibility' => NoteVisibility::INTERNAL->value,
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'category' => NoteCategory::class,
        'visibility' => NoteVisibility::class,
        'creation_source' => CreationSource::class,
    ];

    /**
     * @return HasMany<NoteHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(NoteHistory::class)->latest();
    }

    /**
     * Attachments collection for note files.
     *
     * @return MorphMany<Media, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->media()
            ->where('collection_name', 'attachments')
            ->latest();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('filesystems.default', 'public'));
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'noteable');
    }

    /**
     * @return MorphToMany<People, $this>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'noteable');
    }

    /**
     * @return MorphToMany<Opportunity, $this>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'noteable');
    }

    /**
     * @return MorphToMany<SupportCase, $this>
     */
    public function cases(): MorphToMany
    {
        return $this->morphedByMany(SupportCase::class, 'noteable');
    }

    /**
     * @return MorphToMany<Lead, $this>
     */
    public function leads(): MorphToMany
    {
        return $this->morphedByMany(Lead::class, 'noteable');
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'noteable');
    }

    /**
     * @return MorphToMany<Delivery, $this>
     */
    public function deliveries(): MorphToMany
    {
        return $this->morphedByMany(Delivery::class, 'noteable');
    }

    /**
     * @return MorphToMany<Project, $this>
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'noteable');
    }

    public function isPrivate(): bool
    {
        return $this->visibility === NoteVisibility::PRIVATE;
    }

    public function isExternal(): bool
    {
        return $this->visibility === NoteVisibility::EXTERNAL;
    }

    /**
     * Rich body HTML content.
     */
    public function body(): string
    {
        $field = $this->resolveCustomField(NoteField::BODY->value);

        if (! $field instanceof CustomField) {
            return '';
        }

        $value = $this->getCustomFieldValue($field);

        return is_string($value) ? $value : '';
    }

    /**
     * Plain-text body content for previews/search.
     */
    public function plainBody(): string
    {
        return trim(strip_tags($this->body()));
    }

    private function resolveCustomField(string $code): ?CustomField
    {
        $entityTypes = array_values(array_unique(array_filter([
            Entities::getEntity(self::class)?->getAlias(),
            self::class,
        ])));

        if ($entityTypes === []) {
            return null;
        }

        return CustomField::query()
            ->where('code', $code)
            ->whereIn('entity_type', $entityTypes)
            ->first();
    }
}
