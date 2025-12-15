<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreationSource;
use App\Models\Employee;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
final class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 week', 'now');
        $start = (clone $date)->setTime((int) $this->faker->numberBetween(8, 17), 0);
        $duration = (int) $this->faker->numberBetween(15, 240);
        $end = (clone $start)->modify("+{$duration} minutes");

        return [
            'team_id' => null,
            'employee_id' => Employee::factory(),
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => $duration,
            'description' => $this->faker->sentence(),
            'notes' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
            'is_billable' => $this->faker->boolean(50),
            'billing_rate' => null,
            'billing_amount' => null,
            'project_id' => null,
            'task_id' => null,
            'company_id' => null,
            'time_category_id' => null,
            'timesheet_id' => null,
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'invoice_id' => null,
            'creator_id' => null,
            'editor_id' => null,
            'deleted_by' => null,
            'creation_source' => CreationSource::WEB->value,
            'timezone' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TimeEntry $entry): void {
            if ($entry->team_id !== null) {
                return;
            }

            $employee = Employee::find($entry->employee_id);
            if ($employee instanceof Employee) {
                $entry->team_id = $employee->team_id;
            }
        });
    }
}
