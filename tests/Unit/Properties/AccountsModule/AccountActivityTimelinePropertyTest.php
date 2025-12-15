<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\AccountsModule;

use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\TaskField;
use App\Enums\CustomFieldType;
use App\Models\Account;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

use Relaticle\CustomFields\Services\TenantContextService;

/**
 * **Feature: core-crm-modules, Property 9: Activity timeline completeness**
 *
 * **Validates: Requirements 6.1**
 *
 * Property: Accounts display full activity history (notes/tasks/opportunities/cases) sorted by most recent.
 *
 * For any account with related notes, tasks, opportunities, and cases,
 * the activity timeline should include all related records and be sorted
 * in descending chronological order (most recent first).
 */
test('activity timeline includes all related record types', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'owner_id' => $user->id,
    ]);

    TenantContextService::setTenantId($account->team_id);

    // Create custom fields
    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $team,
    );

    // Create random number of each type
    $noteCount = fake()->numberBetween(1, 3);
    $taskCount = fake()->numberBetween(1, 3);
    $opportunityCount = fake()->numberBetween(1, 3);
    $caseCount = fake()->numberBetween(1, 3);

    // Create notes
    for ($i = 0; $i < $noteCount; $i++) {
        $note = Note::factory()
            ->for($team, 'team')
            ->create([
                'title' => fake()->sentence(),
                'created_at' => now()->subMinutes(fake()->numberBetween(1, 100)),
            ]);
        $account->notes()->attach($note);
        $note->saveCustomFieldValue($noteField, '<p>' . fake()->sentence() . '</p>');
    }

    // Create tasks
    for ($i = 0; $i < $taskCount; $i++) {
        $task = Task::factory()->create([
            'title' => fake()->sentence(),
            'team_id' => $team->id,
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 100)),
        ]);
        $account->tasks()->attach($task);
        $task->saveCustomFieldValue($taskDescriptionField, '<p>' . fake()->sentence() . '</p>');
    }

    // Create opportunities
    for ($i = 0; $i < $opportunityCount; $i++) {
        $opportunity = Opportunity::factory()
            ->for($team, 'team')
            ->create([
                'account_id' => $account->id,
                'created_at' => now()->subMinutes(fake()->numberBetween(1, 100)),
            ]);
        $stageField->loadMissing('options');
        $opportunity->saveCustomFieldValue($stageField, $stageField->options->first()->getKey());
    }

    // Create cases
    for ($i = 0; $i < $caseCount; $i++) {
        SupportCase::factory()->create([
            'team_id' => $team->id,
            'account_id' => $account->id,
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 100)),
        ]);
    }

    $timeline = $account->getActivityTimeline(limit: 100);

    // Verify all types are present
    $types = $timeline->pluck('type')->unique();
    expect($types)->toContain('note')
        ->and($types)->toContain('task')
        ->and($types)->toContain('opportunity')
        ->and($types)->toContain('case')
        ->and($types)->toContain('account'); // account creation event

    // Verify counts (account creation + all related records)
    $noteItems = $timeline->where('type', 'note')->count();
    $taskItems = $timeline->where('type', 'task')->count();
    $opportunityItems = $timeline->where('type', 'opportunity')->count();
    $caseItems = $timeline->where('type', 'case')->count();

    expect($noteItems)->toBe($noteCount)
        ->and($taskItems)->toBe($taskCount)
        ->and($opportunityItems)->toBe($opportunityCount)
        ->and($caseItems)->toBe($caseCount);
})->repeat(10);

/**
 * **Feature: core-crm-modules, Property 9: Activity timeline completeness**
 *
 * **Validates: Requirements 6.1**
 *
 * Property: Activity timeline items are sorted in descending chronological order (most recent first).
 *
 * For any account with activity items, each item's created_at timestamp
 * should be less than or equal to the previous item's timestamp.
 */
test('activity timeline returns items in descending chronological order', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'owner_id' => $user->id,
    ]);

    TenantContextService::setTenantId($account->team_id);

    // Create custom fields
    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $team,
    );

    $itemCount = fake()->numberBetween(5, 15);

    for ($i = 0; $i < $itemCount; $i++) {
        $minutesAgo = fake()->numberBetween(1, 200);
        $createdAt = now()->subMinutes($minutesAgo);

        $type = fake()->randomElement(['note', 'task', 'opportunity', 'case']);

        if ($type === 'note') {
            $note = Note::factory()
                ->for($team, 'team')
                ->create([
                    'title' => fake()->sentence(),
                    'created_at' => $createdAt,
                ]);
            $account->notes()->attach($note);
            $note->saveCustomFieldValue($noteField, '<p>' . fake()->sentence() . '</p>');
        } elseif ($type === 'task') {
            $task = Task::factory()->create([
                'title' => fake()->sentence(),
                'team_id' => $team->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $account->tasks()->attach($task);
            $task->saveCustomFieldValue($taskDescriptionField, '<p>' . fake()->sentence() . '</p>');
        } elseif ($type === 'opportunity') {
            $opportunity = Opportunity::factory()
                ->for($team, 'team')
                ->create([
                    'account_id' => $account->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            $stageField->loadMissing('options');
            $opportunity->saveCustomFieldValue($stageField, $stageField->options->first()->getKey());
        } else {
            SupportCase::factory()->create([
                'team_id' => $team->id,
                'account_id' => $account->id,
                'created_at' => $createdAt,
            ]);
        }
    }

    $timeline = $account->getActivityTimeline(limit: $itemCount + 2); // includes account creation/update

    // Verify items are in descending chronological order (most recent first)
    $previousTimestamp = null;
    foreach ($timeline as $item) {
        expect($item)->toHaveKey('created_at')
            ->and($item['created_at'])->not->toBeNull();

        if ($previousTimestamp !== null) {
            expect($item['created_at']->lte($previousTimestamp))
                ->toBeTrue('Timeline item at ' . $item['created_at'] . ' should be <= ' . $previousTimestamp);
        }
        $previousTimestamp = $item['created_at'];
    }
})->repeat(10);

/**
 * **Feature: core-crm-modules, Property 9: Activity timeline completeness**
 *
 * **Validates: Requirements 6.5**
 *
 * Property: Activity timeline items include required metadata (type, id, title, summary, created_at).
 *
 * For any activity item in the timeline, it should have all required fields
 * populated with appropriate values.
 */
test('activity timeline items contain required metadata fields', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'owner_id' => $user->id,
    ]);

    TenantContextService::setTenantId($account->team_id);

    // Create custom fields
    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $taskDescriptionField = createCustomFieldFor(
        Task::class,
        TaskField::DESCRIPTION->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $team,
    );

    // Create one of each type
    $note = Note::factory()
        ->for($team, 'team')
        ->create(['title' => fake()->sentence()]);
    $account->notes()->attach($note);
    $note->saveCustomFieldValue($noteField, '<p>' . fake()->sentence() . '</p>');

    $task = Task::factory()->create([
        'title' => fake()->sentence(),
        'team_id' => $team->id,
    ]);
    $account->tasks()->attach($task);
    $task->saveCustomFieldValue($taskDescriptionField, '<p>' . fake()->sentence() . '</p>');

    $opportunity = Opportunity::factory()
        ->for($team, 'team')
        ->create(['account_id' => $account->id]);
    $stageField->loadMissing('options');
    $opportunity->saveCustomFieldValue($stageField, $stageField->options->first()->getKey());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'account_id' => $account->id,
    ]);

    $timeline = $account->getActivityTimeline(limit: 100);

    // Verify each item has required fields
    foreach ($timeline as $item) {
        expect($item)->toHaveKeys(['type', 'id', 'title', 'summary', 'created_at'])
            ->and($item['type'])->toBeString()
            ->and($item['type'])->not->toBeEmpty()
            ->and($item['id'])->not->toBeNull()
            ->and($item['title'])->toBeString()
            ->and($item['title'])->not->toBeEmpty()
            ->and($item['created_at'])->not->toBeNull();
    }
})->repeat(10);

/**
 * **Feature: core-crm-modules, Property 9: Activity timeline completeness**
 *
 * **Validates: Requirements 6.1**
 *
 * Property: Activity timeline respects the limit parameter.
 *
 * For any account with more activity items than the specified limit,
 * the timeline should return exactly the limit number of items.
 */
test('activity timeline respects limit parameter', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'owner_id' => $user->id,
    ]);

    TenantContextService::setTenantId($account->team_id);

    // Create custom fields
    $noteField = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team,
    );

    // Create many notes (more than we'll request)
    $totalNotes = fake()->numberBetween(20, 30);
    for ($i = 0; $i < $totalNotes; $i++) {
        $note = Note::factory()
            ->for($team, 'team')
            ->create([
                'title' => fake()->sentence(),
                'created_at' => now()->subMinutes($i),
            ]);
        $account->notes()->attach($note);
        $note->saveCustomFieldValue($noteField, '<p>' . fake()->sentence() . '</p>');
    }

    $limit = fake()->numberBetween(5, 15);
    $timeline = $account->getActivityTimeline(limit: $limit);

    expect($timeline->count())->toBe($limit);
})->repeat(10);

/**
 * **Feature: core-crm-modules, Property 9: Activity timeline completeness**
 *
 * **Validates: Requirements 6.1**
 *
 * Property: Activity timeline handles empty relationships gracefully.
 *
 * For any account with no related records, the timeline should still
 * return the account creation event without errors.
 */
test('activity timeline handles account with no related records', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'owner_id' => $user->id,
    ]);

    $timeline = $account->getActivityTimeline();

    expect($timeline)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($timeline->count())->toBeGreaterThanOrEqual(1)
        ->and($timeline->first()['type'])->toBe('account')
        ->and($timeline->first()['title'])->toContain('Account');
})->repeat(5);
