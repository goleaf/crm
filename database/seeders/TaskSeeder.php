<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskDelegation;
use App\Models\TaskRecurrence;
use App\Models\TaskReminder;
use App\Models\TaskTimeEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating tasks (600) with categories, checklists, comments, and time entries...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();
        Opportunity::all();
        Lead::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        // Create task categories
        $categories = TaskCategory::factory()
            ->count(20)
            ->create([
                'team_id' => fn () => $teams->random()->id,
            ]);

        // Create tasks
        $tasks = Task::factory()
            ->count(600)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'creator_id' => fn () => $users->random()->id,
                'category_id' => fn () => $categories->random()->id,
            ]);

        // Assign tasks to users
        $taskAssignments = [];
        foreach ($tasks as $task) {
            $assignees = $users->random(random_int(1, 3));
            foreach ($assignees as $assignee) {
                $taskAssignments[] = [
                    'task_id' => $task->id,
                    'user_id' => $assignee->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('task_user')->insert($taskAssignments);

        // Attach tasks to entities
        $companyTasks = [];
        foreach ($tasks->random(1000) as $task) {
            $companyTasks[] = [
                'task_id' => $task->id,
                'company_id' => $companies->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('company_task')->insert($companyTasks);

        $peopleTasks = [];
        foreach ($tasks->random(1000) as $task) {
            $peopleTasks[] = [
                'task_id' => $task->id,
                'people_id' => $people->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('people_task')->insert($peopleTasks);

        // Create checklist items
        $checklistItems = [];
        foreach ($tasks->random(1500) as $task) {
            for ($i = 0; $i < random_int(2, 8); $i++) {
                $checklistItems[] = [
                    'task_id' => $task->id,
                    'team_id' => $task->team_id,
                    'title' => fake()->sentence(),
                    'is_completed' => fake()->boolean(40),
                    'position' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($checklistItems, 1000) as $chunk) {
            TaskChecklistItem::insert($chunk);
        }

        // Create comments
        $comments = [];
        foreach ($tasks->random(2000) as $task) {
            for ($i = 0; $i < random_int(1, 10); $i++) {
                $comments[] = [
                    'task_id' => $task->id,
                    'team_id' => $task->team_id,
                    'user_id' => $users->random()->id,
                    'content' => fake()->paragraph(),
                    'created_at' => now()->subDays(random_int(1, 90)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($comments, 1000) as $chunk) {
            TaskComment::insert($chunk);
        }

        // Create delegations
        $delegations = [];
        foreach ($tasks->random(500) as $task) {
            $delegations[] = [
                'task_id' => $task->id,
                'team_id' => $task->team_id,
                'delegated_by' => $users->random()->id,
                'delegated_to' => $users->random()->id,
                'delegated_at' => now()->subDays(random_int(1, 60)),
                'notes' => fake()->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        TaskDelegation::insert($delegations);

        // Create recurrences
        $recurrences = [];
        foreach ($tasks->random(300) as $task) {
            $recurrences[] = [
                'task_id' => $task->id,
                'team_id' => $task->team_id,
                'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
                'interval' => random_int(1, 4),
                'starts_at' => now(),
                'ends_at' => fake()->boolean(50) ? now()->addMonths(random_int(1, 12)) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        TaskRecurrence::insert($recurrences);

        // Create reminders
        $reminders = [];
        foreach ($tasks->random(1000) as $task) {
            for ($i = 0; $i < random_int(1, 3); $i++) {
                $reminders[] = [
                    'task_id' => $task->id,
                    'team_id' => $task->team_id,
                    'user_id' => $users->random()->id,
                    'remind_at' => now()->addDays(random_int(1, 30)),
                    'is_sent' => fake()->boolean(30),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($reminders, 1000) as $chunk) {
            TaskReminder::insert($chunk);
        }

        // Create time entries
        $timeEntries = [];
        foreach ($tasks->random(1500) as $task) {
            for ($i = 0; $i < random_int(1, 5); $i++) {
                $timeEntries[] = [
                    'task_id' => $task->id,
                    'team_id' => $task->team_id,
                    'user_id' => $users->random()->id,
                    'started_at' => now()->subDays(random_int(1, 60)),
                    'ended_at' => now()->subDays(random_int(0, 59)),
                    'duration' => random_int(15, 480),
                    'description' => fake()->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($timeEntries, 1000) as $chunk) {
            TaskTimeEntry::insert($chunk);
        }

        $this->command->info('✓ Created 600 tasks with all related data');
    }
}
