<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Project;
use Tests\Support\Generators\EmployeeGenerator;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 7: Access control
 * Validates: Requirements 1.1, 2.1, 3.1
 *
 * Property: Project and task visibility honor team/role permissions
 * and employee status.
 */
final class AccessControlPropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function projects_are_scoped_to_team(): void
    {
        $this->runPropertyTest(function (): void {
            // Create project in current team
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create another team
            $otherTeam = \App\Models\Team::factory()->create();

            // Property: Project should belong to correct team
            $this->assertEquals(
                $this->team->id,
                $project->team_id,
                'Project should belong to the correct team'
            );

            // Property: Project should not be accessible from other team
            $otherTeamProjects = Project::where('team_id')->get();
            $this->assertFalse(
                $otherTeamProjects->contains('id', $project->id),
                'Project should not be accessible from other team'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function tasks_are_scoped_to_team(): void
    {
        $this->runPropertyTest(function (): void {
            // Create task in current team
            $task = TaskGenerator::generate($this->team, $this->user);

            // Create another team
            $otherTeam = \App\Models\Team::factory()->create();

            // Property: Task should belong to correct team
            $this->assertEquals(
                $this->team->id,
                $task->team_id,
                'Task should belong to the correct team'
            );

            // Property: Task should not be accessible from other team
            $otherTeamTasks = \App\Models\Task::where('team_id', $otherTeam->id)->get();
            $this->assertFalse(
                $otherTeamTasks->contains('id', $task->id),
                'Task should not be accessible from other team'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function employees_are_scoped_to_team(): void
    {
        $this->runPropertyTest(function (): void {
            // Create employee in current team
            $employee = EmployeeGenerator::generate($this->team);

            // Create another team
            $otherTeam = \App\Models\Team::factory()->create();

            // Property: Employee should belong to correct team
            $this->assertEquals(
                $this->team->id,
                $employee->team_id,
                'Employee should belong to the correct team'
            );

            // Property: Employee should not be accessible from other team
            $otherTeamEmployees = Employee::where('team_id', $otherTeam->id)->get();
            $this->assertFalse(
                $otherTeamEmployees->contains('id', $employee->id),
                'Employee should not be accessible from other team'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function inactive_employees_are_identifiable(): void
    {
        $this->runPropertyTest(function (): void {
            // Create inactive employee
            $inactiveStatus = fake()->randomElement([
                EmployeeStatus::INACTIVE,
                EmployeeStatus::TERMINATED,
                EmployeeStatus::ON_LEAVE,
            ]);

            $employee = EmployeeGenerator::generate($this->team, [
                'status' => $inactiveStatus,
            ]);

            // Property: Inactive employee should not be marked as active
            $this->assertFalse(
                $employee->isActive(),
                'Inactive employee should not be marked as active'
            );

            // Property: Only ACTIVE status should return true for isActive()
            $this->assertNotEquals(
                EmployeeStatus::ACTIVE,
                $employee->status,
                'Inactive employee should not have ACTIVE status'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function active_employees_are_identifiable(): void
    {
        $this->runPropertyTest(function (): void {
            // Create active employee
            $employee = EmployeeGenerator::generateActive($this->team);

            // Property: Active employee should be marked as active
            $this->assertTrue(
                $employee->isActive(),
                'Active employee should be marked as active'
            );

            // Property: Active employee should have ACTIVE status
            $this->assertEquals(
                EmployeeStatus::ACTIVE,
                $employee->status,
                'Active employee should have ACTIVE status'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_team_members_are_associated_correctly(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Add team members
            $memberCount = fake()->numberBetween(2, 5);
            $memberIds = [];

            for ($i = 0; $i < $memberCount; $i++) {
                $member = $this->createTeamUsers(1)[0];
                $project->teamMembers()->attach($member->id, [
                    'role' => fake()->jobTitle(),
                    'allocation_percentage' => fake()->numberBetween(20, 100),
                ]);
                $memberIds[] = $member->id;
            }

            // Property: All added members should be retrievable
            $projectMemberIds = $project->teamMembers->pluck('id')->toArray();

            foreach ($memberIds as $memberId) {
                $this->assertContains(
                    $memberId,
                    $projectMemberIds,
                    "Team member {$memberId} should be associated with project"
                );
            }

            // Property: Member count should match
            $this->assertCount(
                $memberCount,
                $project->teamMembers,
                'Project should have correct number of team members'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function task_assignees_are_associated_correctly(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);

            // Add assignees
            $assigneeCount = fake()->numberBetween(1, 4);
            $assigneeIds = [];

            for ($i = 0; $i < $assigneeCount; $i++) {
                $assignee = $this->createTeamUsers(1)[0];
                $task->assignees()->attach($assignee->id);
                $assigneeIds[] = $assignee->id;
            }

            // Property: All added assignees should be retrievable
            $taskAssigneeIds = $task->assignees->pluck('id')->toArray();

            foreach ($assigneeIds as $assigneeId) {
                $this->assertContains(
                    $assigneeId,
                    $taskAssigneeIds,
                    "Assignee {$assigneeId} should be associated with task"
                );
            }

            // Property: Assignee count should match
            $this->assertCount(
                $assigneeCount,
                $task->assignees,
                'Task should have correct number of assignees'
            );
        }, 100);
    }
}
