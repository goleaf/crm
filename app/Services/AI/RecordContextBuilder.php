<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\CustomFields\CompanyField;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\PeopleField;
use App\Enums\CustomFields\TaskField;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Services\Opportunities\OpportunityMetricsService;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class RecordContextBuilder
{
    private const int RELATIONSHIP_LIMIT = 10;

    public function __construct(private OpportunityMetricsService $opportunityMetrics) {}

    /**
     * Build context data for a record to be used in AI summary generation.
     *
     * @return array<string, mixed>
     */
    public function buildContext(Model $record): array
    {
        return match (true) {
            $record instanceof Company => $this->buildCompanyContext($record),
            $record instanceof People => $this->buildPeopleContext($record),
            $record instanceof Opportunity => $this->buildOpportunityContext($record),
            $record instanceof Lead => $this->buildLeadContext($record),
            default => throw new InvalidArgumentException('Unsupported record type: '.$record::class),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCompanyContext(Company $company): array
    {
        $company->loadCount(['notes', 'tasks', 'opportunities', 'people']);

        $company->load([
            'accountOwner',
            'customFieldValues.customField',
            'notes' => $this->recentWithCustomFields('notes'),
            'tasks' => $this->recentWithCustomFields('tasks'),
            'opportunities' => $this->recentWithCustomFields('opportunities'),
        ]);

        return [
            'entity_type' => 'Company',
            'name' => $company->name,
            'basic_info' => $this->getCompanyBasicInfo($company),
            'relationships' => [
                'people_count' => $company->people_count,
                'opportunities_count' => $company->opportunities_count,
            ],
            'opportunities' => $this->formatOpportunities($company->opportunities, $company->opportunities_count),
            'notes' => $this->formatNotes($company->notes, $company->notes_count),
            'tasks' => $this->formatTasks($company->tasks, $company->tasks_count),
            'last_updated' => $company->updated_at?->diffForHumans(),
            'created' => $company->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPeopleContext(People $person): array
    {
        $person->loadCount(['notes', 'tasks']);

        $person->load([
            'company',
            'customFieldValues.customField',
            'notes' => $this->recentWithCustomFields('notes'),
            'tasks' => $this->recentWithCustomFields('tasks'),
        ]);

        return [
            'entity_type' => 'Person',
            'name' => $person->name,
            'basic_info' => $this->getPeopleBasicInfo($person),
            'company' => $person->company?->name,
            'notes' => $this->formatNotes($person->notes, $person->notes_count),
            'tasks' => $this->formatTasks($person->tasks, $person->tasks_count),
            'last_updated' => $person->updated_at?->diffForHumans(),
            'created' => $person->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOpportunityContext(Opportunity $opportunity): array
    {
        $opportunity->loadCount(['notes', 'tasks']);

        $opportunity->load([
            'company',
            'contact',
            'customFieldValues.customField',
            'collaborators',
            'notes' => $this->recentWithCustomFields('notes'),
            'tasks' => $this->recentWithCustomFields('tasks'),
        ]);

        return [
            'entity_type' => 'Opportunity',
            'name' => $this->getOpportunityName($opportunity),
            'basic_info' => $this->getOpportunityBasicInfo($opportunity),
            'company' => $opportunity->company?->name,
            'contact' => $opportunity->contact?->name,
            'collaborators' => $opportunity->collaborators->pluck('name')->all(),
            'notes' => $this->formatNotes($opportunity->notes, $opportunity->notes_count),
            'tasks' => $this->formatTasks($opportunity->tasks, $opportunity->tasks_count),
            'last_updated' => $opportunity->updated_at?->diffForHumans(),
            'created' => $opportunity->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLeadContext(Lead $lead): array
    {
        $lead->loadCount(['notes', 'tasks']);

        $lead->load([
            'company',
            'assignedTo',
            'notes' => $this->recentWithCustomFields('notes'),
            'tasks' => $this->recentWithCustomFields('tasks'),
        ]);

        return [
            'entity_type' => 'Lead',
            'name' => $lead->name,
            'basic_info' => $this->getLeadBasicInfo($lead),
            'company' => $lead->company?->name ?? $lead->company_name,
            'assigned_to' => $lead->assignedTo?->name,
            'status' => $lead->status?->label(),
            'grade' => $lead->grade?->label(),
            'nurture_status' => $lead->nurture_status?->label(),
            'notes' => $this->formatNotes($lead->notes, $lead->notes_count),
            'tasks' => $this->formatTasks($lead->tasks, $lead->tasks_count),
            'last_activity' => $lead->last_activity_at?->diffForHumans(),
            'created' => $lead->created_at?->diffForHumans(),
        ];
    }

    private function recentWithCustomFields(string $table): Closure
    {
        return fn (Relation $query): Relation => $query
            ->with('customFieldValues.customField')
            ->latest("{$table}.created_at")
            ->limit(self::RELATIONSHIP_LIMIT);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCompanyBasicInfo(Company $company): array
    {
        return collect([
            'domain' => $this->getCustomFieldValue($company, CompanyField::DOMAIN_NAME->value),
            'is_icp' => (bool) $this->getCustomFieldValue($company, CompanyField::ICP->value),
            'account_owner' => $company->accountOwner?->name,
        ])->filter(fn (mixed $value): bool => filled($value))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getPeopleBasicInfo(People $person): array
    {
        $emailsFromCustomField = $this->getCustomFieldValue($person, PeopleField::EMAILS->value);
        $phonesFromCustomField = $this->getCustomFieldValue($person, PeopleField::PHONE_NUMBER->value);

        $emails = collect([
            $person->primary_email,
            $person->alternate_email,
        ])
            ->merge(is_array($emailsFromCustomField) ? $emailsFromCustomField : [$emailsFromCustomField])
            ->filter()
            ->unique()
            ->implode(', ');

        $phones = collect([
            $person->phone_mobile,
            $person->phone_office,
            $person->phone_home,
            $person->phone_fax,
        ])
            ->merge(is_array($phonesFromCustomField) ? $phonesFromCustomField : [$phonesFromCustomField])
            ->filter()
            ->unique()
            ->implode(', ');

        return collect([
            'job_title' => $person->job_title ?? $this->getCustomFieldValue($person, PeopleField::JOB_TITLE->value),
            'department' => $person->department,
            'lead_source' => $person->lead_source,
            'emails' => $emails,
            'phones' => $phones,
            'segments' => $person->segments ? implode(', ', $person->segments) : null,
        ])->filter(fn (mixed $value): bool => filled($value))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getOpportunityBasicInfo(Opportunity $opportunity): array
    {
        $amount = $this->opportunityMetrics->amount($opportunity);
        $closeDate = $this->opportunityMetrics->expectedCloseDate($opportunity);
        $probability = $this->opportunityMetrics->probability($opportunity);
        $weightedAmount = $this->opportunityMetrics->weightedAmount($opportunity);
        $forecastCategory = $this->opportunityMetrics->forecastCategory($opportunity);
        $salesCycleDays = $this->opportunityMetrics->salesCycleDays($opportunity);
        $competitors = $this->getCustomFieldValue($opportunity, OpportunityField::COMPETITORS->value);
        $relatedQuotes = $this->getCustomFieldValue($opportunity, OpportunityField::RELATED_QUOTES->value);

        return collect([
            'stage' => $this->opportunityMetrics->stageLabel($opportunity),
            'amount' => $this->formatCurrency($amount),
            'probability' => $probability !== null ? number_format($probability, 0).'%' : null,
            'weighted_amount' => $this->formatCurrency($weightedAmount),
            'forecast_category' => $forecastCategory,
            'close_date' => $this->formatDate($closeDate),
            'sales_cycle_days' => $salesCycleDays !== null ? "{$salesCycleDays} days" : null,
            'next_steps' => $this->stripHtml((string) $this->getCustomFieldValue($opportunity, OpportunityField::NEXT_STEPS->value)),
            'competitors' => is_array($competitors) ? implode(', ', $competitors) : $competitors,
            'related_quotes' => $this->stripHtml((string) $relatedQuotes),
            'win_loss_notes' => $this->stripHtml((string) $this->getCustomFieldValue($opportunity, OpportunityField::OUTCOME_NOTES->value)),
        ])->filter(fn (mixed $value): bool => filled($value))->all();
    }

    private function getOpportunityName(Opportunity $opportunity): string
    {
        $stage = $this->opportunityMetrics->stageLabel($opportunity);

        return $opportunity->company?->name.' - '.($stage ?? 'Opportunity');
    }

    /**
     * @return array<string, mixed>
     */
    private function getLeadBasicInfo(Lead $lead): array
    {
        return collect([
            'email' => $lead->email,
            'phone' => $lead->phone ?? $lead->mobile,
            'website' => $lead->website,
            'source' => $lead->source?->label(),
            'status' => $lead->status?->label(),
            'grade' => $lead->grade?->label(),
            'nurture_status' => $lead->nurture_status?->label(),
            'score' => $lead->score,
            'territory' => $lead->territory,
        ])->filter(fn (mixed $value): bool => filled($value))->all();
    }

    /**
     * @param  Collection<int, Note>  $notes
     * @return array<string, mixed>
     */
    private function formatNotes(Collection $notes, int $totalCount): array
    {
        $formatted = $notes->map(fn (Note $note): array => [
            'title' => $note->title,
            'content' => $this->stripHtml((string) $this->getCustomFieldValue($note, NoteField::BODY->value)),
            'created' => $note->created_at?->diffForHumans(),
        ])->values()->all();

        return $this->withPaginationInfo($formatted, $totalCount);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array<string, mixed>
     */
    private function formatTasks(Collection $tasks, int $totalCount): array
    {
        $formatted = $tasks->map(fn (Task $task): array => [
            'title' => $task->title,
            'status' => $this->getCustomFieldValue($task, TaskField::STATUS->value),
            'priority' => $this->getCustomFieldValue($task, TaskField::PRIORITY->value),
            'due_date' => $this->formatDate($this->getCustomFieldValue($task, TaskField::DUE_DATE->value)),
        ])->values()->all();

        return $this->withPaginationInfo($formatted, $totalCount);
    }

    /**
     * @param  Collection<int, Opportunity>  $opportunities
     * @return array<string, mixed>
     */
    private function formatOpportunities(Collection $opportunities, int $totalCount): array
    {
        $formatted = $opportunities->map(function (Opportunity $opportunity): array {
            $amount = $this->opportunityMetrics->amount($opportunity);
            $probability = $this->opportunityMetrics->probability($opportunity);

            return [
                'stage' => $this->opportunityMetrics->stageLabel($opportunity),
                'amount' => $this->formatCurrency($amount),
                'probability' => $probability !== null ? number_format($probability, 0).'%' : null,
                'weighted_amount' => $this->formatCurrency($this->opportunityMetrics->weightedAmount($opportunity)),
            ];
        })->values()->all();

        return $this->withPaginationInfo($formatted, $totalCount);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function withPaginationInfo(array $items, int $totalCount): array
    {
        $showing = count($items);

        return [
            'items' => $items,
            'showing' => $showing,
            'total' => $totalCount,
            'has_more' => $totalCount > $showing,
        ];
    }

    private function getCustomFieldValue(Model $model, string $code): mixed
    {
        if (! method_exists($model, 'customFieldValues')) {
            return null;
        }

        /** @var Collection<int, \Relaticle\CustomFields\Models\CustomFieldValue> $customFieldValues */
        $customFieldValues = $model->customFieldValues; // @phpstan-ignore property.notFound

        $customFieldValue = $customFieldValues->first(fn (\Relaticle\CustomFields\Models\CustomFieldValue $cfv): bool => $cfv->customField->code === $code);

        if ($customFieldValue === null) {
            return null;
        }

        return $model->getCustomFieldValue($customFieldValue->customField); // @phpstan-ignore method.notFound
    }

    private function formatCurrency(?float $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return '$'.number_format($amount, 2);
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date instanceof \DateTimeInterface
            ? $date->format('M j, Y')
            : (string) $date;
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);

        return Str::limit(trim((string) preg_replace('/\s+/', ' ', $text)), 500);
    }
}
