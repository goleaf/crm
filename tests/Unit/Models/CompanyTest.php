<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\TaskField;
use App\Enums\CustomFieldType;
use App\Enums\Industry;
use App\Models\AccountMerge;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Services\TenantContextService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

uses(RefreshDatabase::class);

test('company belongs to team', function (): void {
    $team = Team::factory()->create();
    $company = Company::factory()->create([
        'team_id' => $team->getKey(),
    ]);

    expect($company->team)->toBeInstanceOf(Team::class)
        ->and($company->team->getKey())->toBe($team->getKey());
});

test('company belongs to creator', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'creator_id' => $user->getKey(),
    ]);

    expect($company->creator)->toBeInstanceOf(User::class)
        ->and($company->creator->getKey())->toBe($user->getKey());
});

test('company belongs to account owner', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'account_owner_id' => $user->getKey(),
    ]);

    expect($company->accountOwner)->toBeInstanceOf(User::class)
        ->and($company->accountOwner->getKey())->toBe($user->getKey());
});

test('company casts account type to enum', function (): void {
    $company = Company::factory()->create([
        'account_type' => AccountType::CUSTOMER,
    ]);

    expect($company->account_type)->toBe(AccountType::CUSTOMER);
});

test('company has many people', function (): void {
    $company = Company::factory()->create();
    $people = People::factory()->create([
        'company_id' => $company->getKey(),
    ]);

    expect($company->people->first())->toBeInstanceOf(People::class)
        ->and($company->people->first()->getKey())->toBe($people->getKey());
});

test('company has many opportunities', function (): void {
    $company = Company::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'company_id' => $company->getKey(),
    ]);

    expect($company->opportunities->first())->toBeInstanceOf(Opportunity::class)
        ->and($company->opportunities->first()->getKey())->toBe($opportunity->getKey());
});

test('company morph to many tasks', function (): void {
    $company = Company::factory()->create();
    $task = Task::factory()->create();

    $company->tasks()->attach($task);

    expect($company->tasks->first())->toBeInstanceOf(Task::class)
        ->and($company->tasks->first()->getKey())->toBe($task->getKey());
});

test('company morph to many notes', function (): void {
    $company = Company::factory()->create();
    $note = Note::factory()->create();

    $company->notes()->attach($note);

    expect($company->notes->first())->toBeInstanceOf(Note::class)
        ->and($company->notes->first()->getKey())->toBe($note->getKey());
});

test('company has logo attribute', function (): void {
    $company = Company::factory()->create([
        'name' => 'Test Company',
    ]);

    expect($company->logo)->not->toBeNull();
});

test('company uses media library', function (): void {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(HasMedia::class)
        ->and(class_uses_recursive($company))->toContain(InteractsWithMedia::class);
});

test('company uses custom fields', function (): void {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(HasCustomFields::class)
        ->and(class_uses_recursive($company))->toContain(UsesCustomFields::class);
});

test('company calculates total pipeline value using open opportunities', function (): void {
    $company = Company::factory()->create();
    TenantContextService::setTenantId($company->team_id);

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $company->team,
    );
    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value,
        [],
        $company->team,
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

    TenantContextService::setTenantId($company->team_id);

    expect($openOpportunity->getCustomFieldValue($amountField))->toBe(150000.0)
        ->and($closedOpportunity->getCustomFieldValue($amountField))->toBe(250000.0);

    expect($company->getTotalPipelineValue())->toBe(150000.0);
});

test('company activity timeline aggregates notes tasks and opportunities in order', function (): void {
    $company = Company::factory()->create();
    TenantContextService::setTenantId($company->team_id);

    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $company->team,
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $company->team,
    );
    $taskStatusField = createCustomFieldFor(
        Task::class,
        TaskField::STATUS->value,
        CustomFieldType::SELECT->value,
        TaskField::STATUS->getOptions() ?? [],
        $company->team,
    );
    $taskPriorityField = createCustomFieldFor(
        Task::class,
        TaskField::PRIORITY->value,
        CustomFieldType::SELECT->value,
        TaskField::PRIORITY->getOptions() ?? [],
        $company->team,
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $company->team,
    );
    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value,
        [],
        $company->team,
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

    TenantContextService::setTenantId($company->team_id);

    $timeline = $company->getActivityTimeline();

    expect($timeline->count())->toBe(4)
        ->and($timeline->first()['type'])->toBe('company') // created event
        ->and(collect($timeline)->pluck('type'))->toContain('note')
        ->and(collect($timeline)->pluck('type'))->toContain('task')
        ->and(collect($timeline)->pluck('type'))->toContain('opportunity')
        ->and($timeline->firstWhere('type', 'note')['summary'])->not->toBeEmpty()
        ->and($timeline->firstWhere('type', 'opportunity')['summary'])->toContain('Amount');
});

// Feature: accounts-module, Property 1: Account creation persistence
test('account creation persists data correctly', function (): void {
    $industry = fake()->randomElement(Industry::cases());
    $data = [
        'name' => fake()->company(),
        'website' => fake()->url(),
        'industry' => $industry,
        'revenue' => fake()->randomFloat(2, 1000, 10000000),
        'employee_count' => fake()->numberBetween(1, 10000),
        'description' => fake()->paragraph(),
        'team_id' => Team::factory()->create()->getKey(),
        'account_owner_id' => User::factory()->create()->getKey(),
    ];

    $company = Company::create($data);
    $retrieved = Company::find($company->getKey());

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->name)->toBe($data['name'])
        ->and($retrieved->website)->toBe($data['website'])
        ->and($retrieved->industry)->toBe($industry)
        ->and((float) $retrieved->revenue)->toBe($data['revenue'])
        ->and($retrieved->employee_count)->toBe($data['employee_count'])
        ->and($retrieved->description)->toBe($data['description']);
})->repeat(10);

// Feature: accounts-module, Property 2: Account update persistence
test('account update persists changes correctly', function (): void {
    $company = Company::factory()->create();
    $industry = fake()->randomElement(Industry::cases());

    $updates = [
        'name' => fake()->company(),
        'website' => fake()->url(),
        'industry' => $industry,
        'revenue' => fake()->randomFloat(2, 1000, 10000000),
        'employee_count' => fake()->numberBetween(1, 10000),
        'description' => fake()->paragraph(),
    ];

    $company->update($updates);
    $retrieved = Company::find($company->getKey());

    expect($retrieved->name)->toBe($updates['name'])
        ->and($retrieved->website)->toBe($updates['website'])
        ->and($retrieved->industry)->toBe($industry)
        ->and((float) $retrieved->revenue)->toBe($updates['revenue'])
        ->and($retrieved->employee_count)->toBe($updates['employee_count'])
        ->and($retrieved->description)->toBe($updates['description']);
})->repeat(10);

test('employee count scope filters by inclusive range', function (): void {
    $small = Company::factory()->create(['employee_count' => 25]);
    $mid = Company::factory()->create(['employee_count' => 250]);
    $large = Company::factory()->create(['employee_count' => 1250]);

    $results = Company::query()
        ->employeeCountBetween(100, 500)
        ->pluck('id');

    expect($results)->toContain($mid->getKey())
        ->and($results)->not->toContain($small->getKey())
        ->and($results)->not->toContain($large->getKey());
});

test('employee count scope supports open ended ranges', function (): void {
    $tiny = Company::factory()->create(['employee_count' => 5]);
    $mid = Company::factory()->create(['employee_count' => 200]);
    $huge = Company::factory()->create(['employee_count' => 4000]);

    $minOnly = Company::query()
        ->employeeCountBetween(150, null)
        ->pluck('id');

    $maxOnly = Company::query()
        ->employeeCountBetween(null, 500)
        ->pluck('id');

    expect($minOnly)->toContain($mid->getKey())
        ->and($minOnly)->not->toContain($tiny->getKey());

    expect($maxOnly)->toContain($mid->getKey())
        ->and($maxOnly)->not->toContain($huge->getKey());
});

// Feature: accounts-module, Property 12: Fuzzy name matching
test('duplicate detection identifies similar names', function (): void {
    $team = Team::factory()->create();
    $baseName = fake()->company();
    $website = 'https://example.test';
    $industry = Industry::TECHNOLOGY;
    $variations = [
        strtoupper($baseName),
        strtolower($baseName),
        $baseName . ' Inc',
        $baseName . ' Corporation',
        $baseName . ' LLC',
    ];

    $original = Company::factory()->for($team)->create([
        'name' => $baseName,
        'website' => $website,
        'industry' => $industry,
    ]);

    foreach ($variations as $variation) {
        $similar = Company::factory()->for($team)->create([
            'name' => $variation,
            'website' => $website,
            'industry' => $industry,
        ]);
        $duplicates = $similar->findPotentialDuplicates();

        expect($duplicates->pluck('company.id'))->toContain($original->getKey());
    }
})->repeat(5);

// Feature: accounts-module, Property 13: Similarity score calculation
test('similarity score is between 0 and 100', function (): void {
    $team = Team::factory()->create();
    $company1 = Company::factory()->for($team)->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
    ]);

    $company2 = Company::factory()->for($team)->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
    ]);

    $score = $company1->calculateSimilarityScore($company2);

    expect($score)->toBeGreaterThanOrEqual(0.0)
        ->and($score)->toBeLessThanOrEqual(100.0);
})->repeat(10);

// Feature: accounts-module, Property 19: Pipeline value calculation
test('pipeline value equals sum of open opportunity amounts', function (): void {
    $company = Company::factory()->create();
    TenantContextService::setTenantId($company->team_id);

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $company->team,
    );
    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value,
        [],
        $company->team,
    );

    $stageField->loadMissing('options');

    $openStage = $stageField->options->firstWhere('name', 'Prospecting') ?? $stageField->options->first();
    $closedWonStage = $stageField->options->firstWhere('name', 'Closed Won');
    $closedLostStage = $stageField->options->firstWhere('name', 'Closed Lost');

    $openAmounts = [];
    $openCount = fake()->numberBetween(1, 5);

    for ($i = 0; $i < $openCount; $i++) {
        $amount = fake()->randomFloat(2, 1000, 100000);
        $openAmounts[] = $amount;

        $opportunity = Opportunity::factory()
            ->for($company->team, 'team')
            ->create(['company_id' => $company->getKey()]);

        $opportunity->saveCustomFieldValue($stageField, $openStage->getKey());
        $opportunity->saveCustomFieldValue($amountField, $amount);
    }

    // Add some closed opportunities that should not be counted
    if ($closedWonStage) {
        $closedOpportunity = Opportunity::factory()
            ->for($company->team, 'team')
            ->create(['company_id' => $company->getKey()]);

        $closedOpportunity->saveCustomFieldValue($stageField, $closedWonStage->getKey());
        $closedOpportunity->saveCustomFieldValue($amountField, fake()->randomFloat(2, 1000, 100000));
    }

    if ($closedLostStage) {
        $lostOpportunity = Opportunity::factory()
            ->for($company->team, 'team')
            ->create(['company_id' => $company->getKey()]);

        $lostOpportunity->saveCustomFieldValue($stageField, $closedLostStage->getKey());
        $lostOpportunity->saveCustomFieldValue($amountField, fake()->randomFloat(2, 1000, 100000));
    }

    $expectedTotal = array_sum($openAmounts);
    TenantContextService::setTenantId($company->team_id);
    $actualTotal = $company->fresh()->getTotalPipelineValue();

    expect($actualTotal)->toBe($expectedTotal);
})->repeat(5);

// Feature: accounts-module, Property 7: Activity chronological ordering
test('activity timeline returns items in descending chronological order', function (): void {
    $company = Company::factory()->create();
    TenantContextService::setTenantId($company->team_id);

    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $company->team,
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $company->team,
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $company->team,
    );

    $itemCount = fake()->numberBetween(3, 10);
    $createdTimes = [];

    for ($i = 0; $i < $itemCount; $i++) {
        $minutesAgo = $i;
        $createdAt = now()->subMinutes($minutesAgo);
        $createdTimes[] = $createdAt;

        $type = fake()->randomElement(['note', 'task', 'opportunity']);

        if ($type === 'note') {
            $note = Note::factory()
                ->for($company->team, 'team')
                ->create([
                    'title' => fake()->sentence(),
                    'created_at' => $createdAt,
                ]);
            $company->notes()->attach($note);
            $note->saveCustomFieldValue($noteField, '<p>' . fake()->sentence() . '</p>');
        } elseif ($type === 'task') {
            $task = Task::factory()->create([
                'title' => fake()->sentence(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $company->tasks()->attach($task);
            $task->saveCustomFieldValue($taskDescriptionField, '<p>' . fake()->sentence() . '</p>');
        } else {
            $opportunity = Opportunity::factory()
                ->for($company->team, 'team')
                ->create([
                    'company_id' => $company->getKey(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            $stageField->loadMissing('options');
            $opportunity->saveCustomFieldValue($stageField, $stageField->options->first()->getKey());
        }
    }

    TenantContextService::setTenantId($company->team_id);

    $timeline = $company->getActivityTimeline(limit: $itemCount + 1); // includes company creation

    // Verify items are in descending chronological order (most recent first)
    $previousTimestamp = null;
    foreach ($timeline as $item) {
        if ($previousTimestamp !== null) {
            expect($item['created_at']->lte($previousTimestamp))->toBeTrue();
        }
        $previousTimestamp = $item['created_at'];
    }
})->repeat(5);

test('company hierarchy cycle detection prevents assigning self or descendant as parent', function (): void {
    $root = Company::factory()->create();
    $child = Company::factory()->create(['parent_company_id' => $root->getKey()]);
    $grandchild = Company::factory()->create(['parent_company_id' => $child->getKey()]);

    // self as parent
    expect($root->wouldCreateCycle($root->getKey()))->toBeTrue();

    // descendant as parent (child/grandchild)
    expect($root->wouldCreateCycle($child->getKey()))->toBeTrue()
        ->and($root->wouldCreateCycle($grandchild->getKey()))->toBeTrue();

    // valid unrelated parent
    $other = Company::factory()->create();
    expect($root->wouldCreateCycle($other->getKey()))->toBeFalse();
});

// Feature: accounts-module, Property 15: Merge relationship transfer
test('merge transfers all relationships from duplicate to primary', function (): void {
    $service = resolve(\App\Services\AccountMergeService::class);

    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    // Create random relationships for duplicate
    $peopleCount = fake()->numberBetween(1, 5);
    $opportunitiesCount = fake()->numberBetween(1, 5);
    $tasksCount = fake()->numberBetween(1, 3);
    $notesCount = fake()->numberBetween(1, 3);

    $people = People::factory()->count($peopleCount)->create([
        'company_id' => $duplicate->getKey(),
    ]);

    $opportunities = Opportunity::factory()->count($opportunitiesCount)->create([
        'company_id' => $duplicate->getKey(),
    ]);

    $tasks = Task::factory()->count($tasksCount)->create();
    foreach ($tasks as $task) {
        $duplicate->tasks()->attach($task);
    }

    $notes = Note::factory()->count($notesCount)->create();
    foreach ($notes as $note) {
        $duplicate->notes()->attach($note);
    }

    // Perform merge
    $result = $service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue();

    // Verify all relationships transferred
    $primary->refresh();
    $duplicate->refresh();

    expect($primary->people()->count())->toBe($peopleCount)
        ->and($primary->opportunities()->count())->toBe($opportunitiesCount)
        ->and($primary->tasks()->count())->toBe($tasksCount)
        ->and($primary->notes()->count())->toBe($notesCount);

    // Verify duplicate is soft deleted
    expect($duplicate->trashed())->toBeTrue();

    // Verify duplicate has no relationships (they were transferred)
    expect($duplicate->people()->count())->toBe(0)
        ->and($duplicate->opportunities()->count())->toBe(0)
        ->and($duplicate->tasks()->count())->toBe(0)
        ->and($duplicate->notes()->count())->toBe(0);
})->repeat(10);

// Feature: accounts-module, Property 17: Merge data preservation
test('merge preserves all unique data based on field selections', function (): void {
    $service = resolve(\App\Services\AccountMergeService::class);

    // Create companies with different non-null field values
    $primary = Company::factory()->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'industry' => fake()->randomElement(Industry::cases()),
        'revenue' => fake()->randomFloat(2, 100000, 1000000),
        'employee_count' => fake()->numberBetween(10, 500),
        'description' => fake()->paragraph(),
        'phone' => fake()->phoneNumber(),
        'primary_email' => fake()->unique()->safeEmail(),
    ]);

    $duplicate = Company::factory()->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'industry' => fake()->randomElement(Industry::cases()),
        'revenue' => fake()->randomFloat(2, 100000, 1000000),
        'employee_count' => fake()->numberBetween(10, 500),
        'description' => fake()->paragraph(),
        'phone' => fake()->phoneNumber(),
        'primary_email' => fake()->unique()->safeEmail(),
    ]);

    // Store original values
    $originalPrimaryName = $primary->name;
    $originalDuplicateWebsite = $duplicate->website;
    $originalDuplicateRevenue = $duplicate->revenue;

    // Select some fields from duplicate
    $fieldSelections = [
        'name' => 'primary',
        'website' => 'duplicate',
        'revenue' => 'duplicate',
        'employee_count' => 'primary',
    ];

    // Perform merge
    $result = $service->merge($primary, $duplicate, $fieldSelections);

    expect($result['success'])->toBeTrue();

    // Verify field selections were applied correctly
    $primary->refresh();

    expect($primary->name)->toBe($originalPrimaryName) // kept primary
        ->and($primary->website)->toBe($originalDuplicateWebsite) // took duplicate
        ->and((float) $primary->revenue)->toBe((float) $originalDuplicateRevenue); // took duplicate

    // Verify no data was lost - duplicate is soft deleted but data preserved
    $duplicate->refresh();
    expect($duplicate->trashed())->toBeTrue();
})->repeat(10);

// Feature: accounts-module, Property 18: Merge transaction rollback
test('merge operation rolls back all changes on error', function (): void {
    $service = resolve(\App\Services\AccountMergeService::class);

    $primary = Company::factory()->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
    ]);

    $duplicate = Company::factory()->create([
        'name' => fake()->company(),
        'website' => fake()->url(),
    ]);

    // Create relationships
    $peopleCount = fake()->numberBetween(1, 3);
    People::factory()->count($peopleCount)->create([
        'company_id' => $duplicate->getKey(),
    ]);

    // Store original state
    $originalPrimaryName = $primary->name;
    $originalDuplicateName = $duplicate->name;
    $originalPrimaryPeopleCount = $primary->people()->count();
    $originalDuplicatePeopleCount = $duplicate->people()->count();

    // Attempt to merge with itself (should fail)
    $result = $service->merge($primary, $primary);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Cannot merge a company with itself');

    // Verify nothing changed
    $primary->refresh();
    expect($primary->name)->toBe($originalPrimaryName)
        ->and($primary->people()->count())->toBe($originalPrimaryPeopleCount)
        ->and($primary->trashed())->toBeFalse();

    // Attempt to merge with a trashed company (should fail)
    $duplicate->delete();
    $result = $service->merge($primary, $duplicate);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Cannot merge deleted companies');

    // Verify primary is still unchanged
    $primary->refresh();
    expect($primary->name)->toBe($originalPrimaryName)
        ->and($primary->people()->count())->toBe($originalPrimaryPeopleCount)
        ->and($primary->trashed())->toBeFalse();
})->repeat(10);

// Feature: accounts-module, Property 16: Merge audit trail
test('merge creates audit trail and soft deletes duplicate', function (): void {
    $service = resolve(\App\Services\AccountMergeService::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $primary = Company::factory()->create([
        'name' => fake()->company(),
    ]);

    $duplicate = Company::factory()->create([
        'name' => fake()->company(),
    ]);

    // Create some relationships to track
    $peopleCount = fake()->numberBetween(1, 3);
    People::factory()->count($peopleCount)->create([
        'company_id' => $duplicate->getKey(),
    ]);

    $fieldSelections = [
        'name' => 'primary',
        'website' => 'duplicate',
    ];

    // Perform merge
    $result = $service->merge($primary, $duplicate, $fieldSelections);

    expect($result['success'])->toBeTrue()
        ->and($result['merge_id'])->not->toBeNull();

    // Verify AccountMerge record was created
    $accountMerge = AccountMerge::find($result['merge_id']);

    expect($accountMerge)->not->toBeNull()
        ->and($accountMerge->primary_company_id)->toBe($primary->getKey())
        ->and($accountMerge->duplicate_company_id)->toBe($duplicate->getKey())
        ->and($accountMerge->merged_by_user_id)->toBe($user->getKey())
        ->and($accountMerge->field_selections)->toBe($fieldSelections)
        ->and($accountMerge->transferred_relationships)->toBeArray()
        ->and($accountMerge->transferred_relationships['people'])->toBe($peopleCount);

    // Verify duplicate is soft deleted
    $duplicate->refresh();
    expect($duplicate->trashed())->toBeTrue();

    // Verify all transferred data is preserved in audit trail
    expect($accountMerge->transferred_relationships)->toHaveKeys(['people', 'opportunities', 'tasks', 'notes']);
})->repeat(10);

// Feature: accounts-module, Property 11: Duplicate detection on creation
test('duplicate detection identifies similar accounts on creation', function (): void {
    $service = resolve(\App\Services\DuplicateDetectionService::class);

    // Create an existing company
    $existing = Company::factory()->create([
        'name' => fake()->company(),
        'website' => 'https://' . fake()->domainName(),
        'industry' => fake()->randomElement(Industry::cases()),
    ]);

    // Create a similar company with slight variations
    $variations = [
        // Same name, same website
        [
            'name' => $existing->name,
            'website' => $existing->website,
            'expectedMinScore' => 90,
        ],
        // Same name, different website
        [
            'name' => $existing->name,
            'website' => 'https://' . fake()->domainName(),
            'expectedMinScore' => 60,
        ],
        // Similar name (with suffix), same website
        [
            'name' => $existing->name . ' LLC',
            'website' => $existing->website,
            'expectedMinScore' => 70,
        ],
        // Similar name (case variation), same domain
        [
            'name' => strtoupper($existing->name),
            'website' => str_replace('https://', 'https://www.', $existing->website),
            'expectedMinScore' => 85,
        ],
    ];

    foreach ($variations as $variation) {
        $newCompany = Company::factory()->create([
            'name' => $variation['name'],
            'website' => $variation['website'],
            'industry' => $existing->industry,
        ]);

        $duplicates = $service->findDuplicates($newCompany, threshold: 50.0);

        expect($duplicates->isNotEmpty())->toBeTrue(
            "Expected to find duplicates for variation: {$variation['name']}",
        );

        $foundExisting = $duplicates->firstWhere('company.id', $existing->getKey());
        expect($foundExisting)->not->toBeNull(
            'Expected to find existing company in duplicates',
        );
        expect($foundExisting['score'])->toBeGreaterThanOrEqual($variation['expectedMinScore']);
    }
})->repeat(3);

// Feature: accounts-module, Property 27: Account type persistence and filtering
test('account type persists and can be filtered', function (): void {
    $team = Team::factory()->create();

    // Generate random account types
    $accountTypes = fake()->randomElements(AccountType::cases(), fake()->numberBetween(2, 6));

    // Create companies with different account types
    $companies = collect($accountTypes)->map(fn (AccountType $type) => Company::factory()->create([
        'team_id' => $team->getKey(),
        'account_type' => $type,
    ]));

    // Test persistence: verify each company has the correct account type
    foreach ($companies as $company) {
        $retrieved = Company::find($company->getKey());
        expect($retrieved->account_type)->toBe($company->account_type)
            ->and($retrieved->account_type)->toBeInstanceOf(AccountType::class);
    }

    // Test filtering: for each unique account type, filter and verify results
    $uniqueTypes = collect($accountTypes)->unique();
    foreach ($uniqueTypes as $type) {
        $filtered = Company::query()
            ->where('team_id', $team->getKey())
            ->where('account_type', $type)
            ->get();

        // All filtered companies should have the specified type
        expect($filtered->every(fn (Company $c): bool => $c->account_type === $type))->toBeTrue();

        // Count should match the number of companies created with this type
        $expectedCount = $companies->filter(fn (Company $c): bool => $c->account_type === $type)->count();
        expect($filtered->count())->toBe($expectedCount);
    }
})->repeat(100);

// Feature: accounts-module, Property 28: Account type change audit trail
test('account type changes are preserved in activity history', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $this->actingAs($user);

    // Create a company with an initial account type
    $initialType = fake()->randomElement(AccountType::cases());
    $company = Company::factory()->create([
        'team_id' => $team->getKey(),
        'account_type' => $initialType,
    ]);

    // Clear any creation activities
    $company->activities()->delete();

    // Change the account type to a different random type
    $availableTypes = array_filter(
        AccountType::cases(),
        fn (AccountType $type): bool => $type !== $initialType,
    );
    $newType = fake()->randomElement($availableTypes);

    // Update using the enum value string to ensure it's detected as a change
    $company->account_type = $newType;
    $company->save();

    // Verify the change was persisted
    $company->refresh();
    expect($company->account_type)->toBe($newType);

    // Verify activity log exists for the update
    $activities = $company->activities()->where('event', 'updated')->get();
    expect($activities->isNotEmpty())->toBeTrue('Expected at least one update activity');

    // Find the activity that logged the account_type change
    $accountTypeChangeActivity = $activities->first(function ($activity): bool {
        $changes = $activity->changes instanceof Collection ? $activity->changes->toArray() : (array) $activity->changes;

        return isset($changes['attributes']['account_type']);
    });

    expect($accountTypeChangeActivity)->not->toBeNull('Expected to find activity logging account_type change');

    // Verify the activity contains the old and new values
    $changes = $accountTypeChangeActivity->changes instanceof Collection
        ? $accountTypeChangeActivity->changes->toArray()
        : (array) $accountTypeChangeActivity->changes;

    expect($changes)->toBeArray()
        ->and($changes['attributes']['account_type'])->toBe($newType->value)
        ->and($changes['old']['account_type'])->toBe($initialType->value);
})->repeat(100);
