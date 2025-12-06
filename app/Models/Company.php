<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\TaskField;
use App\Models\Concerns\HasAiSummary;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Observers\CompanyObserver;
use App\Services\AvatarService;
use App\Services\DuplicateDetectionService;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $name
 * @property string $address
 * @property string $country
 * @property string $phone
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property-read string $created_by
 */
#[ObservedBy(CompanyObserver::class)]
final class Company extends Model implements HasCustomFields, HasMedia
{
    use HasAiSummary;
    use HasCreator;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'country',
        'phone',
        'website',
        'industry',
        'revenue',
        'employee_count',
        'description',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
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
            'revenue' => 'decimal:2',
            'employee_count' => 'integer',
        ];
    }

    /**
     * Cache for resolved custom fields to avoid repeated queries.
     *
     * @var array<string, CustomField|null>
     */
    private static array $customFieldCache = [];

    public function getLogoAttribute(): string
    {
        $logo = $this->getFirstMediaUrl('logo');

        return $logo === '' || $logo === '0' ? app(AvatarService::class)->generateAuto(name: $this->name) : $logo;
    }

    /**
     * Team member responsible for managing the company account
     *
     * @return BelongsTo<User, $this>
     */
    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }

    /**
     * @return HasMany<People, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Find potential duplicate accounts.
     */
    public function findPotentialDuplicates(): Collection
    {
        return app(DuplicateDetectionService::class)->findDuplicates($this);
    }

    /**
     * Calculate how similar another company record is to this one.
     */
    public function calculateSimilarityScore(self $company): float
    {
        return app(DuplicateDetectionService::class)->calculateSimilarity(primary: $this, duplicate: $company);
    }

    /**
     * Total open opportunity value for this company.
     */
    public function getTotalPipelineValue(): float
    {
        /** @var CustomField|null $amountField */
        $amountField = $this->resolveCustomField(Opportunity::class, OpportunityField::AMOUNT->value);

        if ($amountField === null) {
            return 0.0;
        }

        /** @var CustomField|null $stageField */
        $stageField = $this->resolveCustomField(Opportunity::class, OpportunityField::STAGE->value);

        return $this->opportunities()
            ->with('customFieldValues.customField')
            ->get()
            ->map(fn (Opportunity $opportunity): ?float => $this->extractPipelineAmount($opportunity, $amountField, $stageField))
            ->filter(fn (?float $amount): bool => $amount !== null)
            ->sum();
    }

    /**
     * Activity timeline for the company.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActivityTimeline(int $limit = 25): Collection
    {
        $timeline = collect();

        $noteField = $this->resolveCustomField(Note::class, NoteField::BODY->value);
        $taskDescriptionField = $this->resolveCustomField(Task::class, TaskField::DESCRIPTION->value);
        $taskStatusField = $this->resolveCustomField(Task::class, TaskField::STATUS->value);
        $taskPriorityField = $this->resolveCustomField(Task::class, TaskField::PRIORITY->value);
        $opportunityStageField = $this->resolveCustomField(Opportunity::class, OpportunityField::STAGE->value);
        $opportunityAmountField = $this->resolveCustomField(Opportunity::class, OpportunityField::AMOUNT->value);

        $timeline = $timeline->merge(
            $this->notes()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Note $note): array => [
                    'type' => 'note',
                    'id' => $note->getKey(),
                    'title' => $note->title,
                    'summary' => $this->buildSummary([
                        $this->extractCustomFieldValue($noteField, $note),
                    ]),
                    'created_at' => $note->created_at,
                ])
        );

        $timeline = $timeline->merge(
            $this->tasks()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Task $task): array => [
                    'type' => 'task',
                    'id' => $task->getKey(),
                    'title' => $task->title,
                    'summary' => $this->formatTaskSummary($task, $taskStatusField, $taskPriorityField, $taskDescriptionField),
                    'created_at' => $task->created_at,
                ])
        );

        $timeline = $timeline->merge(
            $this->opportunities()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Opportunity $opportunity): array => [
                    'type' => 'opportunity',
                    'id' => $opportunity->getKey(),
                    'title' => $opportunity->name,
                    'summary' => $this->formatOpportunitySummary($opportunity, $opportunityStageField, $opportunityAmountField),
                    'created_at' => $opportunity->created_at,
                ])
        );

        return $timeline
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);
    }

    /**
     * @param  Opportunity  $opportunity
     */
    private function extractPipelineAmount(Opportunity $opportunity, CustomField $amountField, ?CustomField $stageField): ?float
    {
        if ($stageField !== null && ! $this->isOpportunityStageOpen($opportunity, $stageField)) {
            return null;
        }

        $value = $this->extractCustomFieldValue($amountField, $opportunity);

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function isOpportunityStageOpen(Opportunity $opportunity, CustomField $stageField): bool
    {
        $value = $this->extractCustomFieldValue($stageField, $opportunity);

        if ($value === null) {
            return true;
        }

        $label = $this->optionLabel($stageField, $value);

        return ! in_array($label, ['Closed Won', 'Closed Lost'], true);
    }

    private function formatTaskSummary(
        Task $task,
        ?CustomField $statusField,
        ?CustomField $priorityField,
        ?CustomField $descriptionField
    ): string {
        $parts = [];

        if ($statusField !== null) {
            $parts[] = 'Status: '.$this->optionLabel($statusField, $this->extractCustomFieldValue($statusField, $task));
        }

        if ($priorityField !== null) {
            $parts[] = 'Priority: '.$this->optionLabel($priorityField, $this->extractCustomFieldValue($priorityField, $task));
        }

        if ($descriptionField !== null) {
            $parts[] = $this->formatRichText($this->extractCustomFieldValue($descriptionField, $task));
        }

        return $this->buildSummary($parts);
    }

    private function formatOpportunitySummary(Opportunity $opportunity, ?CustomField $stageField, ?CustomField $amountField): string
    {
        $parts = [];

        if ($stageField !== null) {
            $parts[] = 'Stage: '.$this->optionLabel($stageField, $this->extractCustomFieldValue($stageField, $opportunity));
        }

        if ($amountField !== null) {
            $amount = $this->extractCustomFieldValue($amountField, $opportunity);
            if (is_numeric($amount)) {
                $parts[] = 'Amount: $'.number_format((float) $amount, 2);
            }
        }

        return $this->buildSummary($parts);
    }

    /**
     * @param  CustomField|null  $field
     * @param  Model|null  $model
     */
    private function extractCustomFieldValue(?CustomField $field, ?Model $model): mixed
    {
        if ($field === null || $model === null) {
            return null;
        }

        $model->loadMissing('customFieldValues');

        return $model->getCustomFieldValue($field);
    }

    private function buildSummary(array $parts): string
    {
        $filtered = array_filter(
            array_map(
                static fn (mixed $value): string => trim((string) $value),
                $parts
            ),
            static fn (string $value): bool => $value !== ''
        );

        return implode(' • ', $filtered);
    }

    private function formatRichText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value)
            ->stripTags()
            ->trim()
            ->limit(160)
            ->toString();
    }

    private function optionLabel(CustomField $field, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $field->loadMissing('options');

        if (is_numeric($value)) {
            $option = $field->options->firstWhere('id', (int) $value);
            if ($option !== null) {
                return (string) $option->name;
            }
        }

        return (string) $value;
    }

    private function resolveCustomField(string $entity, string $code): ?CustomField
    {
        $cacheKey = "{$entity}:{$code}";

        if (! array_key_exists($cacheKey, self::$customFieldCache)) {
            self::$customFieldCache[$cacheKey] = CustomField::query()
                ->forEntity($entity)
                ->where('code', $code)
                ->first();
        }

        return self::$customFieldCache[$cacheKey];
    }
}
