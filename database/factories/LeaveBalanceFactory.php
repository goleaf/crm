<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
final class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allocated = $this->faker->randomFloat(2, 0, 30);
        $used = $this->faker->randomFloat(2, 0, $allocated);
        $pending = $this->faker->randomFloat(2, 0, max(0, $allocated - $used));
        $carried = $this->faker->randomFloat(2, 0, 10);

        return [
            'team_id' => Team::factory(),
            'employee_id' => fn (array $attributes): int => Employee::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->getKey(),
            'leave_type_id' => fn (array $attributes): int => LeaveType::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->getKey(),
            'year' => (int) now()->format('Y'),
            'allocated_days' => $allocated,
            'used_days' => $used,
            'pending_days' => $pending,
            'carried_over_days' => $carried,
            'available_days' => $allocated + $carried - $used - $pending,
            'expires_at' => null,
        ];
    }
}
