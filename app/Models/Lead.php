<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\TaskField;
use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\TenantContextService;

/**
 * @property Carbon|null    $deleted_at
 * @property CreationSource $creation_source
 * @property LeadStatus     $status
 */
#[ObservedBy(LeadObserver::class)]
#[UsePolicy(\App\Policies\LeadPolicy::class)]
final class Lead extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTags;
    use HasTaxonomies;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'job_title',
        'company_name',
        'company_id',
        'email',
        'phone',
        'mobile',
        'website',
        'description',
        'lead_value',
        'lead_type',
        'expected_close_date',
        'source',
        'status',
        'score',
        'grade',
        'assignment_strategy',
        'assigned_to_id',
        'territory',
        'nurture_status',
        'nurture_program',
        'next_nurture_touch_at',
        'qualified_at',
        'qualification_notes',
        'qualified_by_id',
        'converted_at',
        'converted_by_id',
        'converted_company_id',
        'converted_contact_id',
        'converted_opportunity_id',
        'duplicate_of_id',
        'duplicate_score',
        'last_activity_at',
        'web_form_key',
        'web_form_payload',
        'import_id',
        'creation_source',
        'order_column',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'status' => LeadStatus::NEW,
        'source' => LeadSource::WEBSITE,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL,
        'nurture_status' => LeadNurtureStatus::NOT_STARTED,
    ];

    /**
     * @var array<string, CustomField|null>
     */
    private static array $customFieldCache = [];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'grade' => LeadGrade::class,
            'assignment_strategy' => LeadAssignmentStrategy::class,
            'nurture_status' => LeadNurtureStatus::class,
            'lead_type' => LeadType::class,
            'lead_value' => 'decimal:2',
            'expected_close_date' => 'date',
            'score' => 'integer',
            'qualified_at' => 'datetime',
            'converted_at' => 'datetime',
            'next_nurture_touch_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'web_form_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function qualifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qualified_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_company_id');
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function convertedContact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'converted_contact_id');
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function convertedOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'converted_opportunity_id');
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicate_of_id');
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Scheduled activities (meetings, calls, lunches) linked to the lead.
     *
     * @return MorphMany<CalendarEvent, $this>
     */
    public function calendarEvents(): MorphMany
    {
        return $this->morphMany(CalendarEvent::class, 'related')
            ->latest('start_at');
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    /**
     * Lead activity timeline combining lifecycle events, notes, and tasks.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActivityTimeline(int $limit = 25): Collection
    {
        $timeline = collect([
            [
                'type' => 'lead',
                'id' => $this->getKey(),
                'title' => 'Lead created',
                'summary' => $this->buildSummary([
                    $this->source?->getLabel() ? 'Source: ' . $this->source->getLabel() : null,
                    $this->assignmentSummary(),
                ]),
                'created_at' => $this->created_at,
            ],
        ]);

        if ($this->updated_at !== null && $this->updated_at->gt($this->created_at ?? $this->updated_at)) {
            $timeline = $timeline->push([
                'type' => 'lead',
                'id' => $this->getKey(),
                'title' => 'Lead updated',
                'summary' => $this->buildSummary([
                    $this->status?->getLabel() ? 'Status: ' . $this->status->getLabel() : null,
                    $this->assignmentSummary(),
                ]),
                'created_at' => $this->updated_at,
            ]);
        }

        if ($this->qualified_at !== null) {
            $timeline = $timeline->push([
                'type' => 'qualification',
                'id' => $this->getKey(),
                'title' => 'Lead qualified',
                'summary' => $this->buildSummary([
                    $this->qualifiedBy?->name ? 'Qualified by ' . $this->qualifiedBy->name : null,
                    $this->qualification_notes,
                ]),
                'created_at' => $this->qualified_at,
            ]);
        }

        if ($this->converted_at !== null) {
            $timeline = $timeline->push([
                'type' => 'conversion',
                'id' => $this->getKey(),
                'title' => 'Converted to deal',
                'summary' => $this->buildSummary([
                    $this->convertedCompany?->name ? 'Company: ' . $this->convertedCompany->name : null,
                    $this->convertedOpportunity?->name ? 'Opportunity: ' . $this->convertedOpportunity->name : null,
                ]),
                'created_at' => $this->converted_at,
            ]);
        }

        if ($this->duplicate_of_id !== null) {
            $timeline = $timeline->push([
                'type' => 'duplicate',
                'id' => $this->getKey(),
                'title' => 'Possible duplicate',
                'summary' => $this->buildSummary([
                    $this->duplicateOf?->name ? 'Marked duplicate of ' . $this->duplicateOf->name : 'Marked duplicate',
                    $this->duplicate_score !== null ? 'Confidence: ' . $this->duplicate_score . '%' : null,
                ]),
                'created_at' => $this->updated_at ?? $this->created_at,
            ]);
        }

        $noteField = $this->resolveCustomField(Note::class, NoteField::BODY->value);
        $taskDescriptionField = $this->resolveCustomField(Task::class, TaskField::DESCRIPTION->value);
        $taskStatusField = $this->resolveCustomField(Task::class, TaskField::STATUS->value);
        $taskPriorityField = $this->resolveCustomField(Task::class, TaskField::PRIORITY->value);

        $notesQuery = $this->notes();

        if (method_exists(Note::class, 'customFieldValues')) {
            $notesQuery->with('customFieldValues.customField');
        }

        $timeline = $timeline->merge(
            $notesQuery->get()
                ->map(fn (Note $note): array => [
                    'type' => 'note',
                    'id' => $note->getKey(),
                    'title' => $note->title,
                    'summary' => $this->buildSummary([
                        $this->formatRichText((string) $this->extractCustomFieldValue($noteField, $note)),
                    ]),
                    'created_at' => $note->created_at,
                ]),
        );

        $tasksQuery = $this->tasks();

        if (method_exists(Task::class, 'customFieldValues')) {
            $tasksQuery->with('customFieldValues.customField');
        }

        $timeline = $timeline->merge(
            $tasksQuery->get()
                ->map(fn (Task $task): array => [
                    'type' => 'task',
                    'id' => $task->getKey(),
                    'title' => $task->title,
                    'summary' => $this->formatTaskSummary($task, $taskStatusField, $taskPriorityField, $taskDescriptionField),
                    'created_at' => $task->created_at,
                ]),
        );

        $timeline = $timeline->merge(
            $this->calendarEvents()
                ->get()
                ->map(fn (CalendarEvent $event): array => [
                    'type' => 'activity',
                    'id' => $event->getKey(),
                    'title' => $event->title,
                    'summary' => $this->buildSummary([
                        'Type: ' . $event->type->getLabel(),
                        $this->formatEventSchedule($event),
                        $event->location,
                    ]),
                    'created_at' => $event->start_at ?? $event->created_at,
                ]),
        );

        return $timeline
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);
    }

    private function assignmentSummary(): ?string
    {
        if ($this->assignedTo?->name === null) {
            return null;
        }

        return 'Assigned to ' . $this->assignedTo->name;
    }

    private function formatTaskSummary(
        Task $task,
        ?CustomField $statusField,
        ?CustomField $priorityField,
        ?CustomField $descriptionField,
    ): string {
        $parts = [];

        if ($statusField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = 'Status: ' . $this->optionLabel($statusField, $this->extractCustomFieldValue($statusField, $task));
        }

        if ($priorityField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = 'Priority: ' . $this->optionLabel($priorityField, $this->extractCustomFieldValue($priorityField, $task));
        }

        if ($descriptionField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = $this->formatRichText((string) $this->extractCustomFieldValue($descriptionField, $task));
        } elseif ($task->description) {
            $parts[] = $this->formatRichText((string) $task->description);
        }

        return $this->buildSummary($parts);
    }

    private function formatEventSchedule(CalendarEvent $event): string
    {
        $start = $event->start_at?->format('M j, Y g:i A');
        $end = $event->end_at?->format('M j, Y g:i A');

        if ($start === null && $end === null) {
            return '';
        }

        if ($start !== null && $end !== null) {
            return "{$start} to {$end}";
        }

        return $start ?? (string) $end;
    }

    private function extractCustomFieldValue(?CustomField $field, ?Model $model): mixed
    {
        if (! $field instanceof \Relaticle\CustomFields\Models\CustomField || ! $model instanceof \Illuminate\Database\Eloquent\Model) {
            return null;
        }

        $model->loadMissing('customFieldValues.customField');

        return $model->getCustomFieldValue($field);
    }

    private function buildSummary(array $parts): string
    {
        $filtered = array_filter(
            array_map(
                static fn (mixed $value): string => trim((string) $value),
                $parts,
            ),
            static fn (string $value): bool => $value !== '',
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
        $tenantId = TenantContextService::getCurrentTenantId() ?? 'global';
        $cacheKey = "{$tenantId}:{$entity}:{$code}";

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
            ->forEntity($entity)
            ->where('code', $code)
            ->first();

        return self::$customFieldCache[$cacheKey];
    }
}
