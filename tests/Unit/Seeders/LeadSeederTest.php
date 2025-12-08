<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\LeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seeder = new LeadSeeder;
});

describe('LeadSeeder', function (): void {
    it('creates 600 leads with teams and users', function (): void {
        Team::factory()->count(3)->create();
        User::factory()->count(5)->create();

        $this->seeder->run();

        expect(Lead::count())->toBe(600);
    });

    it('warns and returns early when no teams exist', function (): void {
        User::factory()->count(5)->create();

        $this->seeder->run();

        expect(Lead::count())->toBe(0);
    });

    it('warns and returns early when no users exist', function (): void {
        // Create teams but no users
        Team::factory()->count(3)->create();

        // Delete any users that might have been created
        User::query()->delete();

        // Verify no users exist
        expect(User::count())->toBe(0);

        $this->seeder->run();

        // Should not create leads when no users exist
        expect(Lead::count())->toBe(0);
    });

    it('assigns leads to random teams', function (): void {
        $teams = Team::factory()->count(3)->create();
        User::factory()->count(5)->create();

        $this->seeder->run();

        $leadTeamIds = Lead::pluck('team_id')->unique();

        expect($leadTeamIds->count())->toBeGreaterThan(1)
            ->and($leadTeamIds->every(fn ($id) => $teams->pluck('id')->contains($id)))->toBeTrue();
    });

    it('assigns leads to random users', function (): void {
        Team::factory()->count(3)->create();
        $users = User::factory()->count(5)->create();

        $this->seeder->run();

        $assignedUserIds = Lead::pluck('assigned_to_id')->unique();
        $creatorUserIds = Lead::pluck('creator_id')->unique();

        expect($assignedUserIds->count())->toBeGreaterThan(1)
            ->and($creatorUserIds->count())->toBeGreaterThan(1)
            ->and($assignedUserIds->every(fn ($id) => $users->pluck('id')->contains($id)))->toBeTrue()
            ->and($creatorUserIds->every(fn ($id) => $users->pluck('id')->contains($id)))->toBeTrue();
    });

    it('assigns leads to companies when companies exist', function (): void {
        Team::factory()->count(3)->create();
        User::factory()->count(5)->create();
        $companies = Company::factory()->count(10)->create();

        $this->seeder->run();

        $leadsWithCompanies = Lead::whereNotNull('company_id')->count();

        expect($leadsWithCompanies)->toBeGreaterThan(0);
    });

    it('creates leads without companies when no companies exist', function (): void {
        Team::factory()->count(3)->create();
        User::factory()->count(5)->create();

        $this->seeder->run();

        $leadsWithoutCompanies = Lead::whereNull('company_id')->count();

        expect($leadsWithoutCompanies)->toBe(600);
    });

    it('creates tasks for each lead', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('tasks')->get();

        $leads->each(function ($lead): void {
            expect($lead->tasks->count())->toBeGreaterThanOrEqual(1)
                ->and($lead->tasks->count())->toBeLessThanOrEqual(3);
        });
    });

    it('creates notes for each lead', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('notes')->get();

        $leads->each(function ($lead): void {
            expect($lead->notes->count())->toBeGreaterThanOrEqual(1)
                ->and($lead->notes->count())->toBeLessThanOrEqual(5);
        });
    });

    it('creates activities for each lead', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::all();

        $leads->each(function ($lead): void {
            $activityCount = Activity::where('subject_type', Lead::class)
                ->where('subject_id', $lead->id)
                ->count();

            expect($activityCount)->toBeGreaterThanOrEqual(2)
                ->and($activityCount)->toBeLessThanOrEqual(5);
        });
    });

    it('assigns correct team_id to tasks', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('tasks')->get();

        $leads->each(function ($lead): void {
            $lead->tasks->each(function ($task) use ($lead): void {
                expect($task->team_id)->toBe($lead->team_id);
            });
        });
    });

    it('assigns correct creator_id to tasks', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('tasks')->get();

        $leads->each(function ($lead): void {
            $lead->tasks->each(function ($task) use ($lead): void {
                expect($task->creator_id)->toBe($lead->creator_id);
            });
        });
    });

    it('assigns correct team_id to notes', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('notes')->get();

        $leads->each(function ($lead): void {
            $lead->notes->each(function ($note) use ($lead): void {
                expect($note->team_id)->toBe($lead->team_id);
            });
        });
    });

    it('assigns correct creator_id to notes', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('notes')->get();

        $leads->each(function ($lead): void {
            $lead->notes->each(function ($note) use ($lead): void {
                expect($note->creator_id)->toBe($lead->creator_id);
            });
        });
    });

    it('creates activities with valid event types', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $validEvents = ['created', 'updated', 'status_changed', 'assigned', 'contacted'];
        $activities = Activity::where('subject_type', Lead::class)->get();

        $activities->each(function ($activity) use ($validEvents): void {
            expect($activity->event)->toBeIn($validEvents);
        });
    });

    it('creates activities with changes data', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $activities = Activity::where('subject_type', Lead::class)->get();

        $activities->each(function ($activity): void {
            expect($activity->changes)->toBeArray()
                ->and($activity->changes)->toHaveKey('old')
                ->and($activity->changes)->toHaveKey('new');
        });
    });

    it('processes leads in chunks for memory efficiency', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        // This test verifies the seeder completes without memory issues
        $this->seeder->run();

        expect(Lead::count())->toBe(600);
    });

    it('handles exceptions gracefully when creating leads', function (): void {
        // No teams or users - should warn and return
        $this->seeder->run();

        expect(Lead::count())->toBe(0);
    });

    it('creates total expected number of tasks', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $totalTasks = Task::count();

        // Each lead gets 1-3 tasks, so 600-1800 tasks total
        expect($totalTasks)->toBeGreaterThanOrEqual(600)
            ->and($totalTasks)->toBeLessThanOrEqual(1800);
    });

    it('creates total expected number of notes', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $totalNotes = Note::count();

        // Each lead gets 1-5 notes, so 600-3000 notes total
        expect($totalNotes)->toBeGreaterThanOrEqual(600)
            ->and($totalNotes)->toBeLessThanOrEqual(3000);
    });

    it('creates total expected number of activities', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $totalActivities = Activity::where('subject_type', Lead::class)->count();

        // Each lead gets 2-5 activities, so 1200-3000 activities total
        expect($totalActivities)->toBeGreaterThanOrEqual(1200)
            ->and($totalActivities)->toBeLessThanOrEqual(3000);
    });

    it('maintains referential integrity between leads and tasks', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('tasks')->get();

        $leads->each(function ($lead): void {
            $lead->tasks->each(function ($task) use ($lead): void {
                $pivotExists = \DB::table('lead_task')
                    ->where('lead_id', $lead->id)
                    ->where('task_id', $task->id)
                    ->exists();

                expect($pivotExists)->toBeTrue();
            });
        });
    });

    it('maintains referential integrity between leads and notes', function (): void {
        Team::factory()->count(2)->create();
        User::factory()->count(3)->create();

        $this->seeder->run();

        $leads = Lead::with('notes')->get();

        $leads->each(function ($lead): void {
            $lead->notes->each(function ($note) use ($lead): void {
                $pivotExists = \DB::table('lead_note')
                    ->where('lead_id', $lead->id)
                    ->where('note_id', $note->id)
                    ->exists();

                expect($pivotExists)->toBeTrue();
            });
        });
    });
});
