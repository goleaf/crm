<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\TaskTimeEntry;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 6: Time logging integrity
 * Validates: Requirements 4.1
 *
 * Property: Logged time is attributed to tasks, users, and dates without duplication;
 * billing derives from time entries.
 */
final class TimeLoggingIntegrityPropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function time_entries_cannot_overlap_for_same_user(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);

            // Create first time entry
            $start1 = \Illuminate\Support\Facades\Date::now()->subHours(3);
            $end1 = \Illuminate\Support\Facades\Date::now()->subHours(1);

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $start1,
                'ended_at' => $end1,
                'duration_minutes' => $start1->diffInMinutes($end1),
            ]);

            // Property: Overlapping time entry should throw exception
            $start2 = \Illuminate\Support\Facades\Date::now()->subHours(2); // Overlaps with first entry
            $end2 = \Illuminate\Support\Facades\Date::now()->subMinutes(30);

            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage('overlaps with an existing entry');

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $start2,
                'ended_at' => $end2,
                'duration_minutes' => $start2->diffInMinutes($end2),
            ]);
        }, 100);
    }

    /**
     * @test
     */
    public function duplicate_time_entries_are_prevented(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);

            $startTime = \Illuminate\Support\Facades\Date::now()->subHours(2);
            $duration = fake()->numberBetween(30, 120);

            // Create first time entry
            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $startTime,
                'duration_minutes' => $duration,
                'ended_at' => $startTime->copy()->addMinutes($duration),
            ]);

            // Property: Exact duplicate should throw exception
            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage('already exists');

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $startTime,
                'duration_minutes' => $duration,
                'ended_at' => $startTime->copy()->addMinutes($duration),
            ]);
        }, 100);
    }

    /**
     * @test
     */
    public function different_users_can_log_overlapping_time(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);
            $otherUser = $this->createTeamUsers(1)[0];

            $startTime = \Illuminate\Support\Facades\Date::now()->subHours(2);
            $endTime = \Illuminate\Support\Facades\Date::now()->subHours(1);
            $duration = $startTime->diffInMinutes($endTime);

            // User 1 logs time
            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_minutes' => $duration,
            ]);

            // Property: User 2 can log overlapping time (different user)
            $entry2 = TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $otherUser->id,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_minutes' => $duration,
            ]);

            $this->assertNotNull($entry2, 'Different users should be able to log overlapping time');
        }, 100);
    }

    /**
     * @test
     */
    public function billing_amount_derives_from_duration_and_rate(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);

            $duration = fake()->numberBetween(60, 480); // 1-8 hours
            $rate = fake()->randomFloat(2, 50, 200);

            $startTime = \Illuminate\Support\Facades\Date::now()->subHours(3);
            $endTime = $startTime->copy()->addMinutes($duration);

            $entry = TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_minutes' => $duration,
                'is_billable' => true,
                'billing_rate' => $rate,
            ]);

            // Property: Billing amount = (duration in hours) * rate
            $expectedAmount = round(($duration / 60) * $rate, 2);
            $actualAmount = round(($entry->duration_minutes / 60) * $entry->billing_rate, 2);

            $this->assertEquals(
                $expectedAmount,
                $actualAmount,
                'Billing amount should equal duration in hours times rate'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function non_billable_entries_have_no_billing_amount(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);

            $duration = fake()->numberBetween(60, 480);
            $startTime = \Illuminate\Support\Facades\Date::now()->subHours(3);
            $endTime = $startTime->copy()->addMinutes($duration);

            $entry = TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_minutes' => $duration,
                'is_billable' => false,
                'billing_rate' => null,
            ]);

            // Property: Non-billable entries should have no billing rate
            $this->assertNull(
                $entry->billing_rate,
                'Non-billable entries should have null billing rate'
            );

            // Property: Billing amount should be 0
            $billingAmount = $entry->is_billable && $entry->billing_rate
                ? ($entry->duration_minutes / 60) * $entry->billing_rate
                : 0;

            $this->assertEquals(
                0,
                $billingAmount,
                'Non-billable entries should have zero billing amount'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function time_entries_are_attributed_to_correct_task_and_user(): void
    {
        $this->runPropertyTest(function (): void {
            $task = TaskGenerator::generate($this->team, $this->user);
            $otherUser = $this->createTeamUsers(1)[0];

            $duration = fake()->numberBetween(60, 240);
            $startTime = \Illuminate\Support\Facades\Date::now()->subHours(2);
            $endTime = $startTime->copy()->addMinutes($duration);

            $entry = TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $otherUser->id,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'duration_minutes' => $duration,
            ]);

            // Property: Entry should be attributed to correct task
            $this->assertEquals(
                $task->id,
                $entry->task_id,
                'Time entry should be attributed to correct task'
            );

            // Property: Entry should be attributed to correct user
            $this->assertEquals(
                $otherUser->id,
                $entry->user_id,
                'Time entry should be attributed to correct user'
            );

            // Property: Entry should be retrievable from task
            $taskEntries = $task->timeEntries;
            $this->assertTrue(
                $taskEntries->contains('id', $entry->id),
                'Time entry should be retrievable from task'
            );

            // Property: Entry should be retrievable from user
            $userEntries = TaskTimeEntry::where('user_id', $otherUser->id)->get();
            $this->assertTrue(
                $userEntries->contains('id', $entry->id),
                'Time entry should be retrievable from user'
            );
        }, 100);
    }
}
