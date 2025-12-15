<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DependencyType;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneType;
use App\Models\MilestoneTemplate;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneTemplate>
 */
final class MilestoneTemplateFactory extends Factory
{
    protected $model = MilestoneTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->boolean(70) ? fake()->sentence() : null,
            'category' => fake()->boolean(60) ? fake()->word() : null,
            'template_data' => [
                'milestones' => [
                    [
                        'title' => 'Kickoff',
                        'description' => 'Project kickoff meeting',
                        'target_offset_days' => 0,
                        'milestone_type' => MilestoneType::REVIEW->value,
                        'priority_level' => MilestonePriority::MEDIUM->value,
                        'is_critical' => true,
                        'deliverables' => [
                            [
                                'name' => 'Kickoff notes',
                                'due_offset_days' => 1,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Phase 1 complete',
                        'target_offset_days' => 30,
                        'milestone_type' => MilestoneType::PHASE_COMPLETION->value,
                        'priority_level' => MilestonePriority::HIGH->value,
                        'deliverables' => [
                            [
                                'name' => 'Phase 1 report',
                                'due_offset_days' => 28,
                            ],
                        ],
                    ],
                ],
                'dependencies' => [
                    [
                        'predecessor_index' => 0,
                        'successor_index' => 1,
                        'dependency_type' => DependencyType::FINISH_TO_START->value,
                        'lag_days' => 0,
                    ],
                ],
            ],
            'usage_count' => 0,
        ];
    }
}

