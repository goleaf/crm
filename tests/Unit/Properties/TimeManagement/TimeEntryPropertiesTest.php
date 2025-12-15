<?php

declare(strict_types=1);

use App\Enums\TimeEntryApprovalStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Team;
use App\Models\TimeCategory;
use App\Services\TimeManagement\TimeEntryService;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = \App\Models\User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);

    $this->employee = Employee::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => null,
        'default_billing_rate' => 125.00,
    ]);

    $this->timeCategory = TimeCategory::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $this->company = Company::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $this->timeEntryService = app(TimeEntryService::class);
});

/**
 * Feature: time-management-hr, Property 1: Time Entry Duration Consistency
 *
 * Validates: Requirements 1.1
 */
test('property: time entry duration consistency', function (): void {
    runPropertyTest(function (int $i): void {
        $date = now()->startOfDay()->addDays($i + 1);

        $start = $date->copy()->setTime(9, 0);
        $end = $date->copy()->setTime(9, 0)->addMinutes(90);

        $entry = $this->timeEntryService->createTimeEntry([
            'employee_id' => $this->employee->id,
            'date' => $date->toDateString(),
            'start_time' => $start->toDateTimeString(),
            'end_time' => $end->toDateTimeString(),
            'description' => fake()->sentence(),
            'time_category_id' => $this->timeCategory->id,
        ]);

        expect($entry->duration_minutes)->toBe(90);
    }, 25);
})->group('property');

/**
 * Feature: time-management-hr, Property 2: No Overlapping Time Entries
 *
 * Validates: Requirements 1.4
 */
test('property: no overlapping time entries', function (): void {
    $date = now()->startOfDay()->addDay();

    $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => $date->toDateString(),
        'start_time' => $date->copy()->setTime(9, 0)->toDateTimeString(),
        'end_time' => $date->copy()->setTime(11, 0)->toDateTimeString(),
        'description' => 'Entry 1',
        'time_category_id' => $this->timeCategory->id,
    ]);

    expect(fn (): mixed => $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => $date->toDateString(),
        'start_time' => $date->copy()->setTime(10, 0)->toDateTimeString(),
        'end_time' => $date->copy()->setTime(12, 0)->toDateTimeString(),
        'description' => 'Entry 2',
        'time_category_id' => $this->timeCategory->id,
    ]))->toThrow(ValidationException::class);
})->group('property');

/**
 * Feature: time-management-hr, Property 3: Time Entry Data Completeness
 *
 * Validates: Requirements 1.1
 */
test('property: time entry data completeness', function (): void {
    $entry = $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 60,
        'description' => 'Work',
        'time_category_id' => $this->timeCategory->id,
    ]);

    expect($entry->date)->not->toBeNull()
        ->and($entry->duration_minutes)->toBeGreaterThan(0)
        ->and($entry->employee_id)->toBe($this->employee->id)
        ->and($entry->description)->not->toBeEmpty();
})->group('property');

/**
 * Feature: time-management-hr, Property 4: Time Entry Association Validity
 *
 * Validates: Requirements 1.2
 */
test('property: time entry association validity', function (): void {
    expect(fn (): mixed => $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 30,
        'description' => 'Missing association',
    ]))->toThrow(ValidationException::class);
})->group('property');

/**
 * Feature: time-management-hr, Property 5: Billable Flag and Rate Storage
 *
 * Validates: Requirements 1.3
 */
test('property: billable entries store a positive rate', function (): void {
    $entry = $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 120,
        'description' => 'Billable work',
        'is_billable' => true,
        'company_id' => $this->company->id,
    ]);

    expect($entry->is_billable)->toBeTrue()
        ->and((float) $entry->billing_rate)->toBeGreaterThan(0)
        ->and((float) $entry->billing_rate)->toBe(125.00)
        ->and((float) $entry->billing_amount)->toBe(250.00);
})->group('property');

/**
 * Feature: time-management-hr, Property 6: Duration Within Valid Range
 *
 * Validates: Requirements 2.1
 */
test('property: duration is within 1..1440 minutes', function (): void {
    expect(fn (): mixed => $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 1441,
        'description' => 'Too long',
        'time_category_id' => $this->timeCategory->id,
    ]))->toThrow(ValidationException::class);
})->group('property');

/**
 * Feature: time-management-hr, Property 7: Billable Time Requires Project or Client
 *
 * Validates: Requirements 2.2
 */
test('property: billable time requires project or client', function (): void {
    expect(fn (): mixed => $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 60,
        'description' => 'Billable without client',
        'is_billable' => true,
        'time_category_id' => $this->timeCategory->id,
    ]))->toThrow(ValidationException::class);
})->group('property');

/**
 * Feature: time-management-hr, Property 26: Locked Approved Entries
 *
 * Validates: Requirements 8.4
 */
test('property: approved entries are locked when configured', function (): void {
    config()->set('time-management.time_entries.approval.enabled', false);
    config()->set('time-management.time_entries.approval.lock_approved', true);

    $entry = $this->timeEntryService->createTimeEntry([
        'employee_id' => $this->employee->id,
        'date' => now()->toDateString(),
        'duration_minutes' => 60,
        'description' => 'Approved entry',
        'time_category_id' => $this->timeCategory->id,
    ]);

    expect($entry->approval_status)->toBe(TimeEntryApprovalStatus::APPROVED);

    expect(fn (): mixed => $this->timeEntryService->updateTimeEntry(
        $entry,
        ['description' => 'Updated'],
        actor: null,
    ))->toThrow(ValidationException::class);
})->group('property');
