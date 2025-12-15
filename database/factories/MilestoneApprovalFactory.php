<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MilestoneApprovalStatus;
use App\Models\Milestone;
use App\Models\MilestoneApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneApproval>
 */
final class MilestoneApprovalFactory extends Factory
{
    protected $model = MilestoneApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'milestone_id' => Milestone::factory(),
            'step_order' => 1,
            'approver_id' => User::factory(),
            'approval_criteria' => fake()->boolean(60) ? fake()->sentence() : null,
            'status' => MilestoneApprovalStatus::PENDING,
            'requested_at' => now(),
            'decided_at' => null,
            'decision_comment' => null,
        ];
    }
}

