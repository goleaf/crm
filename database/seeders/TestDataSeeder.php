<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Tests\Support\Generators\NoteGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\Generators\TaskRelatedGenerator;

/**
 * Seeder for generating test data for property-based testing.
 *
 * This seeder creates a comprehensive set of test data including:
 * - Teams and users
 * - Tasks with various configurations
 * - Notes with different visibility levels
 * - Task-related entities (reminders, delegations, time entries, etc.)
 */
final class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teams
        $teams = Team::factory()->count(3)->create();

        foreach ($teams as $team) {
            // Create users for each team
            $users = User::factory()->count(5)->create();

            foreach ($users as $user) {
                $user->teams()->attach($team);
            }

            // Create task categories
            TaskCategory::factory()->count(5)->create(['team_id' => $team->id]);

            // Create various types of tasks
            $this->seedTasks($team, $users);

            // Create notes
            $this->seedNotes($team, $users);
        }
    }

    /**
     * Seed tasks with various configurations.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users
     */
    private function seedTasks(Team $team, $users): void
    {
        // Simple tasks
        for ($i = 0; $i < 10; $i++) {
            TaskGenerator::generate($team, $users->random());
        }

        // Tasks with subtasks
        for ($i = 0; $i < 3; $i++) {
            TaskGenerator::generateWithSubtasks($team, fake()->numberBetween(2, 5));
        }

        // Tasks with assignees
        for ($i = 0; $i < 5; $i++) {
            TaskGenerator::generateWithAssignees($team, fake()->numberBetween(1, 3));
        }

        // Tasks with categories
        for ($i = 0; $i < 5; $i++) {
            TaskGenerator::generateWithCategories($team, fake()->numberBetween(1, 3));
        }

        // Tasks with dependencies
        for ($i = 0; $i < 3; $i++) {
            TaskGenerator::generateWithDependencies($team, fake()->numberBetween(1, 3));
        }

        // Milestone tasks
        for ($i = 0; $i < 3; $i++) {
            TaskGenerator::generateMilestone($team);
        }

        // Tasks with related entities
        $tasksWithRelations = Task::where('team_id', $team->id)->take(5)->get();

        foreach ($tasksWithRelations as $task) {
            // Add reminders
            TaskRelatedGenerator::generateReminder($task, $users->random());

            // Add checklist items
            TaskRelatedGenerator::generateChecklistItems($task, fake()->numberBetween(2, 5));

            // Add comments
            for ($j = 0; $j < fake()->numberBetween(1, 3); $j++) {
                TaskRelatedGenerator::generateComment($task, $users->random());
            }

            // Add time entries
            TaskRelatedGenerator::generateTimeEntries($task, $users->random(), fake()->numberBetween(1, 5));
        }

        // Tasks with recurrence
        $recurringTasks = Task::where('team_id', $team->id)->take(2)->get();

        foreach ($recurringTasks as $task) {
            TaskRelatedGenerator::generateRecurrence($task);
        }

        // Tasks with delegations
        $delegatedTasks = Task::where('team_id', $team->id)->take(2)->get();

        foreach ($delegatedTasks as $task) {
            TaskRelatedGenerator::generateDelegation(
                $task,
                $users->random(),
                $users->random()
            );
        }
    }

    /**
     * Seed notes with various configurations.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users
     */
    private function seedNotes(Team $team, $users): void
    {
        // Notes with different visibility levels
        foreach ($users as $user) {
            NoteGenerator::generatePrivate($team, $user);
            NoteGenerator::generateInternal($team);
            NoteGenerator::generateExternal($team);
        }

        // Notes with all categories
        NoteGenerator::generateAllCategories($team);

        // Note templates
        for ($i = 0; $i < 3; $i++) {
            NoteGenerator::generateTemplate($team);
        }
    }
}
