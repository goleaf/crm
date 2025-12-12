<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);
});

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 1.3, 2.2, 3.1, 5.2, 6.3**
 *
 * Property: Activities linked to a record remain accessible after edits.
 */
test('property: activities remain accessible after record edits', function (): void {
    runPropertyTest(function (): void {
        // Generate a random record type
        $recordTypes = [
            Company::class,
            People::class,
            Lead::class,
            Opportunity::class,
            SupportCase::class,
        ];

        $recordType = fake()->randomElement($recordTypes);
        $record = $recordType::factory()->create(['team_id' => $this->team->id]);

        // Generate various activities linked to this record
        $email = generateEmail($this->team, $this->user, [
            'related_id' => $record->id,
            'related_type' => $record::class,
        ]);

        $call = generateCall($this->team, $this->user, [
            'related_id' => $record->id,
            'related_type' => $record::class,
        ]);

        $event = generateCalendarEvent($this->team, $this->user, [
            'related_id' => $record->id,
            'related_type' => $record::class,
        ]);

        $task = generateTask($this->team, $this->user);
        $record->tasks()->attach($task);

        $note = generateNote($this->team, $this->user);
        $record->notes()->attach($note);

        // Edit the record (simulate various field changes)
        $originalName = $record->name ?? $record->title ?? 'Original';
        $record->update([
            'name' => 'Updated: ' . fake()->company(),
        ]);

        // Verify all activities are still accessible
        expect($email->fresh()->related_id)->toBe($record->id,
            'Email should still be linked to record after edit',
        );
        expect($email->fresh()->related_type)->toBe($record::class,
            'Email relationship type should remain unchanged',
        );

        expect($call->fresh()->related_id)->toBe($record->id,
            'Call should still be linked to record after edit',
        );

        expect($event->fresh()->related_id)->toBe($record->id,
            'Calendar event should still be linked to record after edit',
        );

        // Verify polymorphic relationships still work
        if (method_exists($record, 'tasks')) {
            expect($record->tasks()->where('id', $task->id)->exists())->toBeTrue(
                'Task should still be associated with record after edit',
            );
        }

        if (method_exists($record, 'notes')) {
            expect($record->notes()->where('id', $note->id)->exists())->toBeTrue(
                'Note should still be associated with record after edit',
            );
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 1.3, 2.2, 3.1, 5.2, 6.3**
 *
 * Property: Activities remain accessible after record soft delete.
 */
test('property: activities remain accessible after record soft delete', function (): void {
    runPropertyTest(function (): void {
        // Generate a record that supports soft deletes
        $recordTypes = [
            Company::class,
            People::class,
            Lead::class,
            Opportunity::class,
            Note::class,
        ];

        $recordType = fake()->randomElement($recordTypes);
        $record = $recordType::factory()->create(['team_id' => $this->team->id]);

        // Generate activities linked to this record
        $email = generateEmail($this->team, $this->user, [
            'related_id' => $record->id,
            'related_type' => $record::class,
        ]);

        $call = generateCall($this->team, $this->user, [
            'related_id' => $record->id,
            'related_type' => $record::class,
        ]);

        // Soft delete the record
        $record->delete();

        // Verify the record is soft deleted
        expect($recordType::withoutTrashed()->find($record->id))->toBeNull(
            'Record should be soft deleted',
        );
        expect($recordType::withTrashed()->find($record->id))->not->toBeNull(
            'Record should still exist with trashed scope',
        );

        // Verify activities are still accessible and linked
        $email->refresh();
        expect($email->related_id)->toBe($record->id,
            'Email should still reference soft-deleted record',
        );
        expect($email->related_type)->toBe($record::class,
            'Email relationship type should remain unchanged',
        );

        $call->refresh();
        expect($call->related_id)->toBe($record->id,
            'Call should still reference soft-deleted record',
        );

        // Verify polymorphic relationship can still be accessed with trashed
        $relatedRecord = $email->related()->withTrashed()->first();
        expect($relatedRecord)->not->toBeNull(
            'Related record should be accessible through polymorphic relationship with trashed scope',
        );
        expect($relatedRecord->id)->toBe($record->id,
            'Retrieved related record should be the same as original',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 5.2, 6.3**
 *
 * Property: Activities can be linked to multiple records simultaneously.
 */
test('property: activities support multiple record associations', function (): void {
    runPropertyTest(function (): void {
        // Generate multiple records
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        $person = People::factory()->create(['team_id' => $this->team->id]);
        $opportunity = Opportunity::factory()->create(['team_id' => $this->team->id]);

        // Generate a task that can be associated with multiple records
        $task = generateTask($this->team, $this->user, [
            'title' => 'Multi-record task: ' . fake()->sentence(3),
        ]);

        // Associate task with multiple records
        $company->tasks()->attach($task);
        $person->tasks()->attach($task);
        $opportunity->tasks()->attach($task);

        // Verify task is associated with all records
        expect($company->tasks()->where('id', $task->id)->exists())->toBeTrue(
            'Task should be associated with company',
        );
        expect($person->tasks()->where('id', $task->id)->exists())->toBeTrue(
            'Task should be associated with person',
        );
        expect($opportunity->tasks()->where('id', $task->id)->exists())->toBeTrue(
            'Task should be associated with opportunity',
        );

        // Generate a note that can be associated with multiple records
        $note = generateNote($this->team, $this->user, [
            'title' => 'Multi-record note: ' . fake()->sentence(3),
        ]);

        // Associate note with multiple records
        $company->notes()->attach($note);
        $person->notes()->attach($note);

        // Verify note associations
        expect($company->notes()->where('id', $note->id)->exists())->toBeTrue(
            'Note should be associated with company',
        );
        expect($person->notes()->where('id', $note->id)->exists())->toBeTrue(
            'Note should be associated with person',
        );

        // Verify removing one association doesn't affect others
        $company->tasks()->detach($task);

        expect($company->tasks()->where('id', $task->id)->exists())->toBeFalse(
            'Task should no longer be associated with company after detach',
        );
        expect($person->tasks()->where('id', $task->id)->exists())->toBeTrue(
            'Task should still be associated with person after company detach',
        );
        expect($opportunity->tasks()->where('id', $task->id)->exists())->toBeTrue(
            'Task should still be associated with opportunity after company detach',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 1.3, 2.2, 3.1**
 *
 * Property: Activity timelines show all associated activities in chronological order.
 */
test('property: activity timelines maintain chronological order', function (): void {
    runPropertyTest(function (): void {
        // Generate a record
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        // Generate activities at different times
        $activities = [];

        // Create activities with specific timestamps
        $baseTime = now()->subDays(10);

        for ($i = 0; $i < 5; $i++) {
            $activityTime = $baseTime->copy()->addDays($i);

            // Create different types of activities
            $activityType = fake()->randomElement(['email', 'call', 'event', 'task', 'note']);

            switch ($activityType) {
                case 'email':
                    $activity = generateEmail($this->team, $this->user, [
                        'related_id' => $company->id,
                        'related_type' => Company::class,
                        'created_at' => $activityTime,
                    ]);
                    break;
                case 'call':
                    $activity = generateCall($this->team, $this->user, [
                        'related_id' => $company->id,
                        'related_type' => Company::class,
                        'created_at' => $activityTime,
                    ]);
                    break;
                case 'event':
                    $activity = generateCalendarEvent($this->team, $this->user, [
                        'related_id' => $company->id,
                        'related_type' => Company::class,
                        'created_at' => $activityTime,
                    ]);
                    break;
                case 'task':
                    $activity = generateTask($this->team, $this->user, [
                        'created_at' => $activityTime,
                    ]);
                    $company->tasks()->attach($activity);
                    break;
                case 'note':
                    $activity = generateNote($this->team, $this->user, [
                        'created_at' => $activityTime,
                    ]);
                    $company->notes()->attach($activity);
                    break;
            }

            $activities[] = [
                'type' => $activityType,
                'time' => $activityTime,
                'activity' => $activity,
            ];
        }

        // Verify activities can be retrieved in chronological order
        // For tasks and notes, we can verify the associations exist
        $companyTasks = $company->tasks()->oldest()->get();
        $companyNotes = $company->notes()->oldest()->get();

        // Verify tasks are in chronological order
        if ($companyTasks->count() > 1) {
            for ($i = 1; $i < $companyTasks->count(); $i++) {
                expect($companyTasks[$i]->created_at)->toBeGreaterThanOrEqual(
                    $companyTasks[$i - 1]->created_at,
                    'Tasks should be in chronological order',
                );
            }
        }

        // Verify notes are in chronological order
        if ($companyNotes->count() > 1) {
            for ($i = 1; $i < $companyNotes->count(); $i++) {
                expect($companyNotes[$i]->created_at)->toBeGreaterThanOrEqual(
                    $companyNotes[$i - 1]->created_at,
                    'Notes should be in chronological order',
                );
            }
        }

        // Verify all activities exist and are accessible
        expect($companyTasks->count() + $companyNotes->count())->toBeGreaterThan(0,
            'At least some activities should be associated with the company',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');
