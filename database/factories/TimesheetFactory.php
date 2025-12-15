<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimesheetStatus;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timesheet>
 */
final class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    public function definition(): array
    {
        $periodStart = now()->startOfWeek();
        $periodEnd = $periodStart->copy()->addDays(6);

        return [
            'team_id' => null,
            'employee_id' => Employee::factory(),
            'manager_id' => null,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => TimesheetStatus::DRAFT->value,
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'locked_at' => null,
            'locked_by' => null,
            'deadline_at' => null,
            'reminder_24h_sent_at' => null,
            'reminder_deadline_day_sent_at' => null,
            'auto_submitted_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Timesheet $timesheet): void {
            if ($timesheet->team_id !== null) {
                return;
            }

            $employee = Employee::find($timesheet->employee_id);
            if ($employee instanceof Employee) {
                $timesheet->team_id = $employee->team_id;
            }
        });
    }
}
