<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\Note;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: communication-collaboration, Property 3: Activity association
 * Validates: Requirements 1.3, 2.2, 3.1, 5.2, 6.3
 *
 * Property: For any activity (task, note, calendar event) linked to a CRM record,
 * the activity should remain accessible from that record even after edits or soft deletes.
 */
test('tasks linked to CRM records remain accessible after edits', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $company = Company::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Original Title',
    ]);

    // Link task to company
    $task->companies()->attach($company);

    // Verify initial association
    expect($company->tasks)->toHaveCount(1)
        ->and($company->tasks->first()->id)->toBe($task->id);

    // Edit the task
    $task->update(['title' => 'Updated Title']);

    // Verify association persists after edit
    $company->refresh();
    expect($company->tasks)->toHaveCount(1)
        ->and($company->tasks->first()->id)->toBe($task->id)
        ->and($company->tasks->first()->title)->toBe('Updated Title');

    // Verify activity was logged
    $activities = Activity::query()
        ->where('subject_type', $task->getMorphClass())
        ->where('subject_id', $task->id)
        ->get();

    expect($activities)->toHaveCount(2) // created + updated
        ->and($activities->where('event', 'created')->count())->toBe(1)
        ->and($activities->where('event', 'updated')->count())->toBe(1);
});

test('notes linked to CRM records remain accessible after soft delete', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $company = Company::factory()->create(['team_id' => $team->id]);
    $note = Note::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Important Note',
    ]);

    // Link note to company
    $note->companies()->attach($company);

    // Verify initial association
    expect($company->notes)->toHaveCount(1);

    // Soft delete the note
    $note->delete();

    // Verify note is soft deleted
    expect($note->trashed())->toBeTrue();

    // Verify association still exists (with trashed notes)
    $company->refresh();
    expect($company->notes()->withTrashed()->count())->toBe(1)
        ->and($company->notes()->withTrashed()->first()->id)->toBe($note->id);

    // Verify activity was logged
    $activities = Activity::query()
        ->where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->id)
        ->get();

    expect($activities->where('event', 'deleted')->count())->toBe(1);
});

test('calendar events linked to CRM records remain accessible', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $company = Company::factory()->create(['team_id' => $team->id]);
    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Meeting with Client',
        'related_type' => Company::class,
        'related_id' => $company->id,
    ]);

    // Verify association through polymorphic relation
    expect($event->related)->toBeInstanceOf(Company::class)
        ->and($event->related->id)->toBe($company->id);

    // Update the event
    $event->update(['title' => 'Updated Meeting']);

    // Verify association persists
    $event->refresh();
    expect($event->related)->toBeInstanceOf(Company::class)
        ->and($event->related->id)->toBe($company->id);

    // Verify activity was logged
    $activities = Activity::query()
        ->where('subject_type', $event->getMorphClass())
        ->where('subject_id', $event->id)
        ->get();

    expect($activities)->toHaveCount(2); // created + updated
});

test('activities are accessible through activity service with permissions', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $activityService = resolve(ActivityService::class);

    // Get activities for the task
    $activities = $activityService->getActivitiesFor($task);

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->event)->toBe('created')
        ->and($activities->first()->subject_id)->toBe($task->id);

    // Verify user can view activities
    expect($activityService->canViewActivities($task, $user))->toBeTrue();

    // Create another user in different team
    $otherTeam = Team::factory()->create();
    $otherUser = User::factory()->create();
    $otherUser->teams()->attach($otherTeam);
    $otherUser->switchTeam($otherTeam);

    // Verify other user cannot view activities
    expect($activityService->canViewActivities($task, $otherUser))->toBeFalse();
});

test('multiple edits create multiple activity records', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Version 1',
    ]);

    // Make multiple edits
    $task->update(['title' => 'Version 2']);
    $task->update(['title' => 'Version 3']);
    $task->update(['title' => 'Version 4']);

    // Verify all activities were logged
    $activities = Activity::query()
        ->where('subject_type', $task->getMorphClass())
        ->where('subject_id', $task->id)->oldest()
        ->get();

    expect($activities)->toHaveCount(4) // 1 created + 3 updates
        ->and($activities->first()->event)->toBe('created')
        ->and($activities->skip(1)->take(3)->every(fn ($activity): bool => $activity->event === 'updated'))->toBeTrue();
});

test('activities track the causer correctly', function (): void {
    $team = Team::factory()->create();
    $user1 = User::factory()->create();
    $user1->teams()->attach($team);
    $user1->switchTeam($team);

    $user2 = User::factory()->create();
    $user2->teams()->attach($team);
    $user2->switchTeam($team);

    // User 1 creates task
    $this->actingAs($user1);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user1->id,
    ]);

    // User 2 updates task
    $this->actingAs($user2);
    $task->update(['title' => 'Updated by User 2']);

    // Verify causers
    $activities = Activity::query()
        ->where('subject_type', $task->getMorphClass())
        ->where('subject_id', $task->id)->oldest()
        ->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->causer_id)->toBe($user1->id)
        ->and($activities->last()->causer_id)->toBe($user2->id);
});
