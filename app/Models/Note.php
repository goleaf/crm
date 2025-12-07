<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\InvalidatesRelatedAiSummaries;
use App\Observers\NoteObserver;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\TenantContextService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property NoteVisibility $visibility
 */
#[ObservedBy(NoteObserver::class)]
final class Note extends Model implements HasCustomFields, HasMedia
{
    use HasCreator;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasTeam;
    use InteractsWithMedia;
    use InvalidatesRelatedAiSummaries;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var array<string, CustomField|null>
     */
    private static array $customFieldCache = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
        'creation_source' => CreationSource::WEB,
        'category' => NoteCategory::GENERAL->value,
        'visibility' => NoteVisibility::INTERNAL->value,
        'is_template' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'visibility' => NoteVisibility::class,
            'is_template' => 'boolean',
        ];
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->media()
            ->where('collection_name', 'attachments')
            ->orderByDesc('created_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('filesystems.default', 'public'));
    }

    /**
     * @return HasMany<NoteHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(NoteHistory::class)->latest();
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

    public function isPrivate(): bool
    {
        return $this->visibility === NoteVisibility::PRIVATE;
    }

    public function isExternal(): bool
    {
        return $this->visibility === NoteVisibility::EXTERNAL;
    }

    public function categoryLabel(): string
    {
        return $this->category !== null
            ? (NoteCategory::tryFrom($this->category)?->label() ?? $this->category)
            : 'General';
    }

    public function body(): ?string
    {
        $bodyField = $this->resolveBodyField();

        if (! $bodyField instanceof \Relaticle\CustomFields\Models\CustomField) {
            return null;
        }

        $this->loadMissing('customFieldValues.customField');

        return $this->getCustomFieldValue($bodyField);
    }

    public function plainBody(): string
    {
        return trim(strip_tags((string) $this->body()));
    }

    /**
     * @return Builder<Note>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('team', fn (Builder $builder): Builder => $builder->whereKey($user->currentTeam?->getKey()));
    }

    private function resolveBodyField(): ?CustomField
    {
        $tenantId = TenantContextService::getCurrentTenantId() ?? $this->team_id ?? 'global';
        $cacheKey = "{$tenantId}:note:".NoteField::BODY->value;

        if (array_key_exists($cacheKey, self::$customFieldCache)) {
            $cached = self::$customFieldCache[$cacheKey];

            if (! $cached instanceof \Relaticle\CustomFields\Models\CustomField) {
                return null;
            }

            if ($cached->exists && CustomField::query()->whereKey($cached->getKey())->exists()) {
                return $cached;
            }

            unset(self::$customFieldCache[$cacheKey]);
        }

        self::$customFieldCache[$cacheKey] = CustomField::query()
            ->forEntity(self::class)
            ->where('code', NoteField::BODY->value)
            ->first();

        return self::$customFieldCache[$cacheKey];
    }
}
