<?php

declare(strict_types=1);

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Team;
use App\Services\TimeManagement\AbsenceService;
use App\Services\TimeManagement\LeaveBalanceService;
use Illuminate\Support\Facades\Date;
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
    ]);

    $this->leaveType = LeaveType::factory()->create([
        'team_id' => $this->team->id,
        'requires_approval' => true,
        'is_active' => true,
    ]);

    LeaveBalance::factory()->create([
        'team_id' => $this->team->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => (int) now()->format('Y'),
        'allocated_days' => 10,
        'used_days' => 0,
        'pending_days' => 0,
        'carried_over_days' => 0,
        'available_days' => 10,
    ]);

    $this->absenceService = app(AbsenceService::class);
    $this->leaveBalanceService = app(LeaveBalanceService::class);
});

/**
 * Feature: time-management-hr, Property 17: Absence Data Completeness
 *
 * Validates: Requirements 5.1
 */
test('property: absence data completeness', function (): void {
    $absence = $this->absenceService->createAbsence([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'reason' => 'Vacation',
    ]);

    expect($absence->employee_id)->toBe($this->employee->id)
        ->and($absence->leave_type_id)->toBe($this->leaveType->id)
        ->and($absence->start_date)->not->toBeNull()
        ->and($absence->end_date)->not->toBeNull()
        ->and($absence->duration_days)->not->toBeNull();
})->group('property');

/**
 * Feature: time-management-hr, Property 20: Absence Duration Calculation
 *
 * Validates: Requirements 5.5
 */
test('property: absence duration excludes weekends', function (): void {
    config()->set('laravel-crm.business_hours.holidays', []);

    $start = Date::parse('next friday')->startOfDay();
    $end = $start->copy()->addDays(3); // Friday -> Monday (includes weekend)

    $absence = Absence::create([
        'team_id' => $this->team->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'status' => AbsenceStatus::PENDING,
        'reason' => 'Test',
    ]);

    expect((float) $absence->duration_days)->toBe(2.0)
        ->and((float) $absence->duration_hours)->toBe(16.0);
})->group('property');

/**
 * Feature: time-management-hr, Property 22: Leave Balance Consistency
 *
 * Validates: Requirements 6.3
 */
test('property: leave balance consistency', function (): void {
    $otherLeaveType = LeaveType::factory()->create([
        'team_id' => $this->team->id,
        'requires_approval' => true,
        'is_active' => true,
    ]);

    $balance = LeaveBalance::factory()->create([
        'team_id' => $this->team->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $otherLeaveType->id,
        'allocated_days' => 12,
        'used_days' => 3,
        'pending_days' => 2,
        'carried_over_days' => 1,
        'available_days' => 0,
    ]);

    $balance->recalculate();
    $balance->save();

    expect((float) $balance->available_days)->toBe(8.0);
})->group('property');

/**
 * Feature: time-management-hr, Property 19: Leave Balance Deduction on Approval
 *
 * Validates: Requirements 5.4
 */
test('property: approving an absence moves pending to used', function (): void {
    $start = now()->addWeek()->startOfWeek();
    $end = $start->copy();

    $absence = $this->absenceService->createAbsence([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'reason' => 'Vacation',
    ]);

    $balance = $this->leaveBalanceService->getBalance($this->employee, $this->leaveType, (int) now()->format('Y'));
    $balance->refresh();

    expect((float) $balance->pending_days)->toBe(1.0)
        ->and((float) $balance->used_days)->toBe(0.0);

    $this->absenceService->approveAbsence($absence->fresh(), $this->user);

    $balance->refresh();

    expect((float) $balance->pending_days)->toBe(0.0)
        ->and((float) $balance->used_days)->toBe(1.0);
})->group('property');

/**
 * Feature: time-management-hr, Property 23: Insufficient Balance Validation
 *
 * Validates: Requirements 6.4
 */
test('property: requesting leave beyond balance is rejected', function (): void {
    $balance = $this->leaveBalanceService->getBalance($this->employee, $this->leaveType, (int) now()->format('Y'));
    $balance->allocated_days = 0;
    $balance->used_days = 0;
    $balance->pending_days = 0;
    $balance->carried_over_days = 0;
    $balance->recalculate();
    $balance->save();

    $businessDay = Date::parse('next monday')->toDateString();

    expect(fn (): mixed => $this->absenceService->createAbsence([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => $businessDay,
        'end_date' => $businessDay,
        'reason' => 'Vacation',
    ]))->toThrow(ValidationException::class);
})->group('property');
