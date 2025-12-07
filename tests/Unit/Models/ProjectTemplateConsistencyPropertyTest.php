<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

/**
 * Feature: projects-resources, Property 5: Template consistency
 *
 * For any project template, creating a new project from that template should
 * apply all tasks, milestones, phases, and default settings without omission.
 *
 * Validates: Requirements 1.3
 */
test('property: creating projects from templates preserves all template attributes', function () {
    // Run 100 iterations to test with various random data
    for ($i = 0; $i < 100; $i++) {
        $team = Team::factory()->create();
        $creator = User::factory()->create();

        // Create a template with random attributes
        $template = Project::factory()
            ->template()
            ->create([
                'team_id' => $team->id,
                'creator_id' => $creator->id,
                'name' => 'Template '.$i,
                'description' => fake()->paragraph(),
                'budget' => fake()->randomFloat(2, 10000, 500000),
                'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
                'phases' => [
                    [
                        'name' => 'Phase 1',
                        'start_date' => now()->format('Y-m-d'),
                        'end_date' => now()->addWeeks(2)->format('Y-m-d'),
                        'status' => 'pending',
                    ],
                    [
                        'name' => 'Phase 2',
                        'start_date' => now()->addWeeks(2)->format('Y-m-d'),
                        'end_date' => now()->addWeeks(4)->format('Y-m-d'),
                        'status' => 'pending',
                    ],
                ],
                'milestones' => [
                    [
                        'name' => 'Milestone 1',
                        'date' => now()->addWeeks(1)->format('Y-m-d'),
                        'completed' => false,
                    ],
                    [
                        'name' => 'Milestone 2',
                        'date' => now()->addWeeks(3)->format('Y-m-d'),
                        'completed' => false,
                    ],
                ],
                'deliverables' => [
                    ['name' => 'Deliverable 1', 'description' => 'First deliverable'],
                    ['name' => 'Deliverable 2', 'description' => 'Second deliverable'],
                ],
                'dashboard_config' => [
                    'widgets' => ['progress', 'budget', 'tasks'],
                    'layout' => 'grid',
                ],
            ]);

        // Add team members to template
        $teamMemberCount = fake()->numberBetween(1, 5);
        for ($j = 0; $j < $teamMemberCount; $j++) {
            $member = User::factory()->create();
            $template->teamMembers()->attach($member->id, [
                'role' => fake()->randomElement(['Developer', 'Designer', 'Manager']),
                'allocation_percentage' => fake()->randomFloat(2, 25, 100),
            ]);
        }

        // Add tasks to template
        $taskCount = fake()->numberBetween(1, 10);
        for ($j = 0; $j < $taskCount; $j++) {
            $task = Task::factory()->create(['team_id' => $team->id]);
            $template->tasks()->attach($task->id);
        }

        // Create a new project from the template
        $newProject = $template->createFromTemplate('New Project '.$i);

        // Verify all template attributes are preserved
        expect($newProject->template_id)->toBe($template->id)
            ->and($newProject->description)->toBe($template->description)
            ->and($newProject->budget)->toBe($template->budget)
            ->and($newProject->currency)->toBe($template->currency)
            ->and($newProject->phases)->toBe($template->phases)
            ->and($newProject->milestones)->toBe($template->milestones)
            ->and($newProject->deliverables)->toBe($template->deliverables)
            ->and($newProject->dashboard_config)->toBe($template->dashboard_config)
            ->and($newProject->is_template)->toBeFalse()
            ->and($newProject->status)->toBe(ProjectStatus::PLANNING);

        // Verify team members are copied
        expect($newProject->teamMembers)->toHaveCount($template->teamMembers->count());

        foreach ($template->teamMembers as $templateMember) {
            $copiedMember = $newProject->teamMembers->firstWhere('id', $templateMember->id);
            expect($copiedMember)->not->toBeNull()
                ->and($copiedMember->pivot->role)->toBe($templateMember->pivot->role)
                ->and($copiedMember->pivot->allocation_percentage)->toBe($templateMember->pivot->allocation_percentage);
        }

        // Verify tasks are copied
        expect($newProject->tasks)->toHaveCount($template->tasks->count());

        foreach ($template->tasks as $templateTask) {
            $copiedTask = $newProject->tasks->firstWhere('id', $templateTask->id);
            expect($copiedTask)->not->toBeNull();
        }
    }
})->group('property-based');

/**
 * Feature: projects-resources, Property 5: Template consistency
 *
 * Edge case: Empty template should create valid empty project
 *
 * Validates: Requirements 1.3
 */
test('property: creating project from empty template works correctly', function () {
    $team = Team::factory()->create();

    // Create a minimal template with no team members or tasks
    $template = Project::factory()
        ->template()
        ->create([
            'team_id' => $team->id,
            'name' => 'Empty Template',
            'description' => null,
            'budget' => null,
            'phases' => null,
            'milestones' => null,
            'deliverables' => null,
            'dashboard_config' => null,
        ]);

    $newProject = $template->createFromTemplate('New Empty Project');

    expect($newProject->template_id)->toBe($template->id)
        ->and($newProject->description)->toBeNull()
        ->and($newProject->budget)->toBeNull()
        ->and($newProject->phases)->toBeNull()
        ->and($newProject->milestones)->toBeNull()
        ->and($newProject->deliverables)->toBeNull()
        ->and($newProject->dashboard_config)->toBeNull()
        ->and($newProject->teamMembers)->toHaveCount(0)
        ->and($newProject->tasks)->toHaveCount(0)
        ->and($newProject->is_template)->toBeFalse();
})->group('property-based');

/**
 * Feature: projects-resources, Property 5: Template consistency
 *
 * Overrides should not affect template-derived attributes unless explicitly specified
 *
 * Validates: Requirements 1.3
 */
test('property: template overrides work correctly', function () {
    for ($i = 0; $i < 50; $i++) {
        $team = Team::factory()->create();

        $template = Project::factory()
            ->template()
            ->create([
                'team_id' => $team->id,
                'name' => 'Template',
                'description' => 'Original description',
                'budget' => 10000,
                'currency' => 'USD',
            ]);

        // Override specific attributes
        $newBudget = fake()->randomFloat(2, 5000, 50000);
        $newCurrency = fake()->randomElement(['EUR', 'GBP']);

        $newProject = $template->createFromTemplate('New Project', [
            'budget' => $newBudget,
            'currency' => $newCurrency,
        ]);

        // Overridden attributes should use new values
        expect((float) $newProject->budget)->toBe($newBudget)
            ->and($newProject->currency)->toBe($newCurrency);

        // Non-overridden attributes should use template values
        expect($newProject->description)->toBe($template->description)
            ->and($newProject->template_id)->toBe($template->id);
    }
})->group('property-based');
