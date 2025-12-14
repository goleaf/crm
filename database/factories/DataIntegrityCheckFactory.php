<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DataIntegrityCheckStatus;
use App\Enums\DataIntegrityCheckType;
use App\Models\DataIntegrityCheck;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataIntegrityCheck>
 */
final class DataIntegrityCheckFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DataIntegrityCheck>
     */
    protected $model = DataIntegrityCheck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(DataIntegrityCheckType::cases());

        return [
            'team_id' => Team::factory(),
            'type' => $type,
            'status' => $this->faker->randomElement(DataIntegrityCheckStatus::cases()),
            'target_model' => $this->faker->optional()->randomElement([
                \App\Models\Company::class,
                \App\Models\People::class,
                \App\Models\Opportunity::class,
                \App\Models\Task::class,
            ]),
            'check_parameters' => [
                'auto_fix' => $this->faker->boolean(),
                'fix_method' => $this->faker->randomElement(['delete', 'nullify']),
                'batch_size' => $this->faker->numberBetween(100, 1000),
            ],
            'results' => [
                'issues' => [
                    [
                        'type' => 'orphaned_records',
                        'table' => 'people',
                        'count' => $this->faker->numberBetween(0, 50),
                        'description' => 'Found orphaned records in people table',
                    ],
                ],
                'summary' => 'Found ' . $this->faker->numberBetween(0, 100) . ' issues',
            ],
            'issues_found' => $this->faker->numberBetween(0, 100),
            'issues_fixed' => $this->faker->numberBetween(0, 50),
            'created_by' => User::factory(),
            'started_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the integrity check is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataIntegrityCheckStatus::PENDING,
            'started_at' => null,
            'completed_at' => null,
            'results' => null,
            'issues_found' => 0,
            'issues_fixed' => 0,
        ]);
    }

    /**
     * Indicate that the integrity check is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataIntegrityCheckStatus::RUNNING,
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'results' => null,
        ]);
    }

    /**
     * Indicate that the integrity check is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataIntegrityCheckStatus::COMPLETED,
            'started_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the integrity check has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataIntegrityCheckStatus::FAILED,
            'error_message' => $this->faker->sentence(),
            'started_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'results' => null,
            'issues_found' => 0,
            'issues_fixed' => 0,
        ]);
    }
}
