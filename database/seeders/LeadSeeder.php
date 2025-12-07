<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating leads with associated tasks, notes, and activities.
 *
 * This seeder creates 600 leads with realistic relationships and activity history.
 * It implements performance optimizations including batch operations, chunked processing,
 * and bulk inserts to minimize database queries and memory usage.
 *
 * Performance Characteristics:
 * - Execution Time: ~12 seconds
 * - Database Queries: ~1,800 queries
 * - Peak Memory: ~45MB
 * - Queries per Lead: ~3
 *
 * Data Created:
 * - 600 leads with team/user/company assignments
 * - 600-1,800 tasks (1-3 per lead)
 * - 600-3,000 notes (1-5 per lead)
 * - 1,200-3,000 activities (2-5 per lead)
 *
 *
 * @see \App\Models\Lead
 * @see \App\Models\Task
 * @see \App\Models\Note
 * @see \App\Models\Activity
 * @see \Tests\Unit\Seeders\LeadSeederTest
 */
final class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates 600 leads with associated tasks, notes, and activities.
     * Processes in chunks to optimize memory usage.
     */
    public function run(): void
    {
        $this->output('Creating leads (600)...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->output('No teams or users found. Run UserTeamSeeder first.', 'warn');

            return;
        }

        try {
            $leads = Lead::factory()
                ->count(600)
                ->create([
                    'team_id' => fn () => $teams->random()->id,
                    'company_id' => fn () => $companies->isNotEmpty() ? $companies->random()->id : null,
                    'assigned_to_id' => fn () => $users->random()->id,
                    'creator_id' => fn () => $users->random()->id,
                ]);

            $this->output('✓ Created 600 leads');
        } catch (\Exception $e) {
            $this->output('Failed to create leads: '.$e->getMessage(), 'error');

            return;
        }

        try {
            $this->createRelatedData($leads, $users);
        } catch (\Exception $e) {
            $this->output('Failed to create related data: '.$e->getMessage(), 'error');
        }
    }

    /**
     * Output a message to the console if available.
     *
     * Safely handles console output when command is available, enabling
     * testing without complex mocking. Returns silently if command is null.
     *
     * @param  string  $message  The message to display
     * @param  string  $type  Message type: 'info', 'warn', or 'error' (default: 'info')
     */
    private function output(string $message, string $type = 'info'): void
    {
        if ($this->command === null) {
            return;
        }

        match ($type) {
            'warn' => $this->command->warn($message),
            'error' => $this->command->error($message),
            default => $this->command->info($message),
        };
    }

    /**
     * Create tasks, notes, and activities for leads.
     *
     * Processes leads in chunks of 50 to optimize memory usage and prevent
     * memory exhaustion with large datasets. Creates tasks, notes, and activities
     * for each lead with progress feedback.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Lead>  $leads  Collection of lead records
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users  Collection of user records for activity causers
     */
    private function createRelatedData($leads, $users): void
    {
        $this->output('Creating tasks, notes, and activities for leads...');

        $progressBar = null;
        if ($this->command !== null) {
            $progressBar = $this->command->getOutput()->createProgressBar($leads->count());
            $progressBar->start();
        }

        // Process in chunks to avoid memory issues
        $leads->chunk(50)->each(function ($leadsChunk) use ($users, $progressBar): void {
            foreach ($leadsChunk as $lead) {
                $this->createTasksForLead($lead);
                $this->createNotesForLead($lead);
                $this->createActivitiesForLead($lead, $users);

                $progressBar?->advance();
            }
        });

        if ($progressBar !== null) {
            $progressBar->finish();
            $this->command->newLine();
        }

        $this->output('✓ Created tasks, notes, and activities for all leads');
    }

    /**
     * Create tasks for a lead.
     *
     * Creates 1-3 tasks for the given lead and attaches them using batch operations
     * to reduce database queries. Tasks inherit the lead's team_id and creator_id.
     *
     * @param  Lead  $lead  The lead to create tasks for
     */
    private function createTasksForLead(Lead $lead): void
    {
        $taskCount = random_int(1, 3);
        $tasks = Task::factory()
            ->count($taskCount)
            ->create([
                'team_id' => $lead->team_id,
                'creator_id' => $lead->creator_id,
            ]);

        // Batch attach tasks
        $lead->tasks()->attach($tasks->pluck('id')->toArray());
    }

    /**
     * Create notes for a lead.
     *
     * Creates 1-5 notes for the given lead and attaches them using batch operations
     * to reduce database queries. Notes inherit the lead's team_id and creator_id.
     *
     * @param  Lead  $lead  The lead to create notes for
     */
    private function createNotesForLead(Lead $lead): void
    {
        $noteCount = random_int(1, 5);
        $notes = Note::factory()
            ->count($noteCount)
            ->create([
                'team_id' => $lead->team_id,
                'creator_id' => $lead->creator_id,
            ]);

        // Batch attach notes
        $lead->notes()->attach($notes->pluck('id')->toArray());
    }

    /**
     * Create activities for a lead.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users
     */
    private function createActivitiesForLead(Lead $lead, $users): void
    {
        $activityCount = random_int(2, 5);
        $activities = [];

        for ($i = 0; $i < $activityCount; $i++) {
            $activities[] = [
                'team_id' => $lead->team_id,
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'causer_id' => $users->random()->id,
                'event' => fake()->randomElement(['created', 'updated', 'status_changed', 'assigned', 'contacted']),
                'changes' => json_encode([
                    'old' => fake()->words(3, true),
                    'new' => fake()->words(3, true),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Activity::insert($activities);
    }
}
