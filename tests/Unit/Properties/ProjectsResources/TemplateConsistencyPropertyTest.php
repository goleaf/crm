<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\Project;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 5: Template consistency
 * Validates: Requirements 1.1, 1.3
 *
 * Property: Creating projects from templates applies tasks, milestones,
 * and default settings without omission.
 */
final class TemplateConsistencyPropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function project_from_template_copies_all_template_properties(): void
    {
        $this->runPropertyTest(function (): void {
            // Create a template with various properties
            $template = ProjectGenerator::generateTemplate($this->team, $this->user, [
                'description' => fake()->paragraph(),
                'budget' => fake()->randomFloat(2, 5000, 50000),
                'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
                'phases' => ['Phase 1', 'Phase 2', 'Phase 3'],
                'milestones' => [
                    ['name' => 'Milestone 1', 'date' => '2024-06-01'],
                    ['name' => 'Milestone 2', 'date' => '2024-09-01'],
                ],
                'deliverables' => ['Deliverable 1', 'Deliverable 2'],
            ]);

            // Create project from template
            $projectName = fake()->words(3, true);
            $project = $template->createFromTemplate($projectName);

            // Property: Project should copy template description
            $this->assertEquals(
                $template->description,
                $project->description,
                'Project should copy template description'
            );

            // Property: Project should copy template budget
            $this->assertEquals(
                $template->budget,
                $project->budget,
                'Project should copy template budget'
            );

            // Property: Project should copy template currency
            $this->assertEquals(
                $template->currency,
                $project->currency,
                'Project should copy template currency'
            );

            // Property: Project should copy template phases
            $this->assertEquals(
                $template->phases,
                $project->phases,
                'Project should copy template phases'
            );

            // Property: Project should copy template milestones
            $this->assertEquals(
                $template->milestones,
                $project->milestones,
                'Project should copy template milestones'
            );

            // Property: Project should copy template deliverables
            $this->assertEquals(
                $template->deliverables,
                $project->deliverables,
                'Project should copy template deliverables'
            );

            // Property: Project should reference the template
            $this->assertEquals(
                $template->id,
                $project->template_id,
                'Project should reference the template'
            );

            // Property: Project should not be a template itself
            $this->assertFalse(
                $project->is_template,
                'Project created from template should not be a template'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_from_template_copies_team_members(): void
    {
        $this->runPropertyTest(function (): void {
            $template = ProjectGenerator::generateTemplate($this->team, $this->user);

            // Add team members to template
            $memberCount = fake()->numberBetween(2, 5);
            $members = [];

            for ($i = 0; $i < $memberCount; $i++) {
                $member = $this->createTeamUsers(1)[0];
                $template->teamMembers()->attach($member->id, [
                    'role' => fake()->jobTitle(),
                    'allocation_percentage' => fake()->numberBetween(20, 100),
                ]);
                $members[] = $member->id;
            }

            // Create project from template
            $project = $template->createFromTemplate(fake()->words(3, true));

            // Property: Project should have same team members as template
            $projectMemberIds = $project->teamMembers->pluck('id')->sort()->values()->toArray();
            $templateMemberIds = collect($members)->sort()->values()->toArray();

            $this->assertEquals(
                $templateMemberIds,
                $projectMemberIds,
                'Project should copy all team members from template'
            );

            // Property: Team member roles and allocations should be copied
            foreach ($template->teamMembers as $templateMember) {
                $projectMember = $project->teamMembers->firstWhere('id', $templateMember->id);

                $this->assertNotNull($projectMember, "Team member {$templateMember->id} should exist in project");
                $this->assertEquals(
                    $templateMember->pivot->role,
                    $projectMember->pivot->role,
                    'Team member role should be copied'
                );
                $this->assertEquals(
                    $templateMember->pivot->allocation_percentage,
                    $projectMember->pivot->allocation_percentage,
                    'Team member allocation should be copied'
                );
            }
        }, 100);
    }

    /**
     * @test
     */
    public function project_from_template_copies_associated_tasks(): void
    {
        $this->runPropertyTest(function (): void {
            $template = ProjectGenerator::generateTemplate($this->team, $this->user);

            // Add tasks to template
            $taskCount = fake()->numberBetween(2, 6);
            $taskIds = [];

            for ($i = 0; $i < $taskCount; $i++) {
                $task = TaskGenerator::generate($this->team, $this->user);
                $template->tasks()->attach($task->id);
                $taskIds[] = $task->id;
            }

            // Create project from template
            $project = $template->createFromTemplate(fake()->words(3, true));

            // Property: Project should have same tasks as template
            $projectTaskIds = $project->tasks->pluck('id')->sort()->values()->toArray();
            $templateTaskIds = collect($taskIds)->sort()->values()->toArray();

            $this->assertEquals(
                $templateTaskIds,
                $projectTaskIds,
                'Project should copy all tasks from template'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function cannot_create_project_from_non_template(): void
    {
        $this->runPropertyTest(function (): void {
            // Create a regular project (not a template)
            $regularProject = ProjectGenerator::generate($this->team, $this->user, [
                'is_template' => false,
            ]);

            // Property: Attempting to create from non-template should throw exception
            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage('Cannot create project from non-template');

            $regularProject->createFromTemplate(fake()->words(3, true));
        }, 100);
    }

    /**
     * @test
     */
    public function project_from_template_allows_overrides(): void
    {
        $this->runPropertyTest(function (): void {
            $template = ProjectGenerator::generateTemplate($this->team, $this->user, [
                'budget' => 10000,
                'currency' => 'USD',
            ]);

            // Create project with overrides
            $overrideBudget = fake()->randomFloat(2, 20000, 50000);
            $overrideCurrency = 'EUR';

            $project = $template->createFromTemplate(
                fake()->words(3, true),
                [
                    'budget' => $overrideBudget,
                    'currency' => $overrideCurrency,
                ]
            );

            // Property: Overrides should take precedence over template values
            $this->assertEquals(
                $overrideBudget,
                $project->budget,
                'Override budget should take precedence'
            );

            $this->assertEquals(
                $overrideCurrency,
                $project->currency,
                'Override currency should take precedence'
            );
        }, 100);
    }
}
