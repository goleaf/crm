<?php

declare(strict_types=1);

use App\Enums\CustomFieldType;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\TaskField;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

uses(RefreshDatabase::class);

test('company belongs to team', function () {
    $team = Team::factory()->create();
    $company = Company::factory()->create([
        'team_id' => $team->getKey(),
    ]);

    expect($company->team)->toBeInstanceOf(Team::class)
        ->and($company->team->getKey())->toBe($team->getKey());
});

test('company belongs to creator', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'creator_id' => $user->getKey(),
    ]);

    expect($company->creator)->toBeInstanceOf(User::class)
        ->and($company->creator->getKey())->toBe($user->getKey());
});

test('company belongs to account owner', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'account_owner_id' => $user->getKey(),
    ]);

    expect($company->accountOwner)->toBeInstanceOf(User::class)
        ->and($company->accountOwner->getKey())->toBe($user->getKey());
});

test('company has many people', function () {
    $company = Company::factory()->create();
    $people = People::factory()->create([
        'company_id' => $company->getKey(),
    ]);

    expect($company->people->first())->toBeInstanceOf(People::class)
        ->and($company->people->first()->getKey())->toBe($people->getKey());
});

test('company has many opportunities', function () {
    $company = Company::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'company_id' => $company->getKey(),
    ]);

    expect($company->opportunities->first())->toBeInstanceOf(Opportunity::class)
        ->and($company->opportunities->first()->getKey())->toBe($opportunity->getKey());
});

test('company morph to many tasks', function () {
    $company = Company::factory()->create();
    $task = Task::factory()->create();

    $company->tasks()->attach($task);

    expect($company->tasks->first())->toBeInstanceOf(Task::class)
        ->and($company->tasks->first()->getKey())->toBe($task->getKey());
});

test('company morph to many notes', function () {
    $company = Company::factory()->create();
    $note = Note::factory()->create();

    $company->notes()->attach($note);

    expect($company->notes->first())->toBeInstanceOf(Note::class)
        ->and($company->notes->first()->getKey())->toBe($note->getKey());
});

test('company has logo attribute', function () {
    $company = Company::factory()->create([
        'name' => 'Test Company',
    ]);

    expect($company->logo)->not->toBeNull();
});

test('company uses media library', function () {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(HasMedia::class)
        ->and(class_uses_recursive($company))->toContain(InteractsWithMedia::class);
});

test('company uses custom fields', function () {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(HasCustomFields::class)
        ->and(class_uses_recursive($company))->toContain(UsesCustomFields::class);
});

test('company calculates total pipeline value using open opportunities', function () {
    $company = Company::factory()->create();

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? []
    );
    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value
    );

    $stageField->loadMissing('options');

    $prospecting = $stageField->options->firstWhere('name', 'Prospecting') ?? $stageField->options->first();
    $closedWon = $stageField->options->firstWhere('name', 'Closed Won') ?? $stageField->options->last();

    $openOpportunity = Opportunity::factory()
        ->for($company->team, 'team')
        ->create([
            'company_id' => $company->getKey(),
        ]);

    $closedOpportunity = Opportunity::factory()
        ->for($company->team, 'team')
        ->create([
            'company_id' => $company->getKey(),
        ]);

    $openOpportunity->saveCustomFieldValue($stageField, $prospecting?->getKey());
    $openOpportunity->saveCustomFieldValue($amountField, 150000);

    $closedOpportunity->saveCustomFieldValue($stageField, $closedWon?->getKey());
    $closedOpportunity->saveCustomFieldValue($amountField, 250000);

    expect($company->getTotalPipelineValue())->toBe(150000.0);
});

test('company activity timeline aggregates notes tasks and opportunities in order', function () {
    $company = Company::factory()->create();

    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value
    );
    $taskStatusField = createCustomFieldFor(
        Task::class,
        TaskField::STATUS->value,
        CustomFieldType::SELECT->value,
        TaskField::STATUS->getOptions() ?? []
    );
    $taskPriorityField = createCustomFieldFor(
        Task::class,
        TaskField::PRIORITY->value,
        CustomFieldType::SELECT->value,
        TaskField::PRIORITY->getOptions() ?? []
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? []
    );
    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value
    );

    $stageField->loadMissing('options');
    $taskStatusField->loadMissing('options');
    $taskPriorityField->loadMissing('options');

    $note = Note::factory()
        ->for($company->team, 'team')
        ->create([
            'title' => 'Big update',
            'created_at' => now()->subMinutes(1),
        ]);

    $company->notes()->attach($note);
    $note->saveCustomFieldValue($noteField, '<p>Customer is interested in renewal.</p>');

    $task = Task::factory()->create([
        'title' => 'Follow up',
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $company->tasks()->attach($task);

    $task->saveCustomFieldValue($taskStatusField, $taskStatusField->options->first()->getKey());
    $task->saveCustomFieldValue($taskPriorityField, $taskPriorityField->options->first()->getKey());
    $task->saveCustomFieldValue($taskDescriptionField, '<p>Follow up with procurement.</p>');

    $opportunity = Opportunity::factory()
        ->for($company->team, 'team')
        ->create([
            'company_id' => $company->getKey(),
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);

    $stageOption = $stageField->options->firstWhere('name', 'Prospecting') ?? $stageField->options->first();
    $opportunity->saveCustomFieldValue($stageField, $stageOption->getKey());
    $opportunity->saveCustomFieldValue($amountField, 50000);

    $timeline = $company->getActivityTimeline();

    expect($timeline->count())->toBe(3)
        ->and($timeline->first()['type'])->toBe('note')
        ->and($timeline->skip(1)->first()['type'])->toBe('task')
        ->and($timeline->last()['type'])->toBe('opportunity')
        ->and($timeline->first()['summary'])->not->toBeEmpty()
        ->and($timeline->skip(2)->first()['summary'])->toContain('Amount');
});

/**
 * @param  array<int, string>  $options
 */
function createCustomFieldFor(string $entity, string $code, string $type, array $options = []): CustomField
{
    $factory = CustomField::factory()->state([
        'entity_type' => $entity,
        'code' => $code,
        'name' => Str::headline(str_replace('_', ' ', $code)),
        'type' => $type,
        'system_defined' => true,
        'active' => true,
        'sort_order' => 1,
    ]);

    if ($options !== []) {
        $factory = $factory->withOptions($options);
    }

    return $factory->create();
}
