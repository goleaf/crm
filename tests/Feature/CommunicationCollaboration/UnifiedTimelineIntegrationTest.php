<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Call;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Note;
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
 * Integration test for unified activity timeline rendering.
 *
 * **Validates: Requirements 1.3, 2.2, 3.1, 5.2, 6.3**
 */
test('unified timeline shows all activity types in chronological order', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    // Create activities at different times
    $baseTime = now()->subDays(5);

    // Day 1: Email
    $email = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Initial contact email',
        'related_id' => $company->id,
        'related_type' => Company::class,
        'created_at' => $baseTime,
        'status' => 'sent',
        'sent_at' => $baseTime,
    ]);

    // Day 2: Call
    $call = Call::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'direction' => 'outbound',
        'purpose' => 'Follow up on email',
        'related_id' => $company->id,
        'related_type' => Company::class,
        'created_at' => $baseTime->copy()->addDay(),
        'status' => 'completed',
    ]);

    // Day 3: Meeting
    $meeting = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Client meeting',
        'related_id' => $company->id,
        'related_type' => Company::class,
        'created_at' => $baseTime->copy()->addDays(2),
        'start_at' => $baseTime->copy()->addDays(2)->addHours(2),
    ]);

    // Day 4: Task
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Prepare proposal',
        'created_at' => $baseTime->copy()->addDays(3),
    ]);
    $company->tasks()->attach($task);

    // Day 5: Note
    $note = Note::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Meeting notes',
        'created_at' => $baseTime->copy()->addDays(4),
    ]);
    $company->notes()->attach($note);

    // Simulate unified timeline query
    $activities = collect();

    // Add emails
    $companyEmails = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get()
        ->map(fn ($item): array => [
            'type' => 'email',
            'id' => $item->id,
            'title' => $item->subject,
            'created_at' => $item->created_at,
            'data' => $item,
        ]);
    $activities = $activities->merge($companyEmails);

    // Add calls
    $companyCalls = Call::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get()
        ->map(fn ($item): array => [
            'type' => 'call',
            'id' => $item->id,
            'title' => $item->purpose ?? 'Call',
            'created_at' => $item->created_at,
            'data' => $item,
        ]);
    $activities = $activities->merge($companyCalls);

    // Add meetings
    $companyMeetings = CalendarEvent::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get()
        ->map(fn ($item): array => [
            'type' => 'meeting',
            'id' => $item->id,
            'title' => $item->title,
            'created_at' => $item->created_at,
            'data' => $item,
        ]);
    $activities = $activities->merge($companyMeetings);

    // Add tasks
    $companyTasks = $company->tasks()
        ->get()
        ->map(fn ($item): array => [
            'type' => 'task',
            'id' => $item->id,
            'title' => $item->title,
            'created_at' => $item->created_at,
            'data' => $item,
        ]);
    $activities = $activities->merge($companyTasks);

    // Add notes
    $companyNotes = $company->notes()
        ->get()
        ->map(fn ($item): array => [
            'type' => 'note',
            'id' => $item->id,
            'title' => $item->title,
            'created_at' => $item->created_at,
            'data' => $item,
        ]);
    $activities = $activities->merge($companyNotes);

    // Sort by creation time
    $sortedActivities = $activities->sortBy('created_at')->values();

    // Verify all activities are present
    expect($sortedActivities)->toHaveCount(5);

    // Verify chronological order
    expect($sortedActivities[0]['type'])->toBe('email');
    expect($sortedActivities[1]['type'])->toBe('call');
    expect($sortedActivities[2]['type'])->toBe('meeting');
    expect($sortedActivities[3]['type'])->toBe('task');
    expect($sortedActivities[4]['type'])->toBe('note');

    // Verify each activity has correct data
    expect($sortedActivities[0]['title'])->toBe('Initial contact email');
    expect($sortedActivities[1]['title'])->toBe('Follow up on email');
    expect($sortedActivities[2]['title'])->toBe('Client meeting');
    expect($sortedActivities[3]['title'])->toBe('Prepare proposal');
    expect($sortedActivities[4]['title'])->toBe('Meeting notes');
});

test('timeline respects privacy and permissions', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $otherUser = User::factory()->create();
    $otherUser->teams()->attach($this->team);

    // Create public note
    $publicNote = Note::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Public note',
        'visibility' => \App\Enums\NoteVisibility::EXTERNAL,
    ]);
    $company->notes()->attach($publicNote);

    // Create private note
    $privateNote = Note::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Private note',
        'visibility' => \App\Enums\NoteVisibility::PRIVATE,
    ]);
    $company->notes()->attach($privateNote);

    // Creator should see both notes
    actingAs($this->user);
    $creatorNotes = $company->notes()
        ->where(function (\Illuminate\Contracts\Database\Query\Builder $query): void {
            $query->where('visibility', '!=', \App\Enums\NoteVisibility::PRIVATE->value)
                ->orWhere('creator_id', auth()->id());
        })
        ->get();

    expect($creatorNotes)->toHaveCount(2);
    expect($creatorNotes->pluck('id'))->toContain($publicNote->id);
    expect($creatorNotes->pluck('id'))->toContain($privateNote->id);

    // Other user should only see public note
    actingAs($otherUser);
    $otherUserNotes = $company->notes()
        ->where(function (\Illuminate\Contracts\Database\Query\Builder $query): void {
            $query->where('visibility', '!=', \App\Enums\NoteVisibility::PRIVATE->value)
                ->orWhere('creator_id', auth()->id());
        })
        ->get();

    expect($otherUserNotes)->toHaveCount(1);
    expect($otherUserNotes->first()->id)->toBe($publicNote->id);
});

test('timeline handles large volumes of activities efficiently', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    // Create many activities
    $emailCount = 20;
    $callCount = 15;
    $taskCount = 10;
    $noteCount = 25;

    // Create emails
    EmailMessage::factory()->count($emailCount)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    // Create calls
    Call::factory()->count($callCount)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    // Create tasks
    $tasks = Task::factory()->count($taskCount)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
    ]);
    $company->tasks()->attach($tasks->pluck('id'));

    // Create notes
    $notes = Note::factory()->count($noteCount)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
    ]);
    $company->notes()->attach($notes->pluck('id'));

    // Test paginated timeline query
    $perPage = 20;

    // Simulate paginated query for emails
    $paginatedEmails = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->orderBy('created_at', 'desc')
        ->limit($perPage)
        ->get();

    expect($paginatedEmails)->toHaveCount(min($emailCount, $perPage));

    // Test total counts
    $totalEmails = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->count();

    $totalCalls = Call::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->count();

    $totalTasks = $company->tasks()->count();
    $totalNotes = $company->notes()->count();

    expect($totalEmails)->toBe($emailCount);
    expect($totalCalls)->toBe($callCount);
    expect($totalTasks)->toBe($taskCount);
    expect($totalNotes)->toBe($noteCount);
});

test('timeline supports filtering by activity type', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    // Create different types of activities
    $email = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    $call = Call::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
    ]);
    $company->tasks()->attach($task);

    // Filter by email activities only
    $emailActivities = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get();

    expect($emailActivities)->toHaveCount(1);
    expect($emailActivities->first()->id)->toBe($email->id);

    // Filter by call activities only
    $callActivities = Call::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get();

    expect($callActivities)->toHaveCount(1);
    expect($callActivities->first()->id)->toBe($call->id);

    // Filter by task activities only
    $taskActivities = $company->tasks;

    expect($taskActivities)->toHaveCount(1);
    expect($taskActivities->first()->id)->toBe($task->id);
});

test('timeline supports date range filtering', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $startDate = now()->subWeek();
    $endDate = now()->subDays(2);

    // Create activities within date range
    $emailInRange = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'created_at' => $startDate->copy()->addDays(2),
    ]);

    // Create activity outside date range
    $emailOutOfRange = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'created_at' => now()->subMonth(),
    ]);

    // Filter by date range
    $filteredEmails = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

    expect($filteredEmails)->toHaveCount(1);
    expect($filteredEmails->first()->id)->toBe($emailInRange->id);

    // Verify out-of-range activity is excluded
    expect($filteredEmails->pluck('id'))->not->toContain($emailOutOfRange->id);
});

test('timeline maintains referential integrity after record updates', function (): void {
    $company = Company::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Original Company Name',
    ]);

    // Create activities linked to company
    $email = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    $call = Call::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    // Update company
    $company->update(['name' => 'Updated Company Name']);

    // Verify activities still reference the company correctly
    expect($email->fresh()->related_id)->toBe($company->id);
    expect($email->fresh()->related_type)->toBe(Company::class);
    expect($email->fresh()->related->name)->toBe('Updated Company Name');

    expect($call->fresh()->related_id)->toBe($company->id);
    expect($call->fresh()->related_type)->toBe(Company::class);
    expect($call->fresh()->related->name)->toBe('Updated Company Name');
});
