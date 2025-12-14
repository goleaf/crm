<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BulkOperationStatus;
use App\Enums\BulkOperationType;
use App\Models\BulkOperation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BulkOperation>
 */
final class BulkOperationFactory extends Factory
{
    protected $model = BulkOperation::class;

    public function definition(): array
    {
        $totalRecords = $this->faker->numberBetween(10, 1000);
        $processedRecords = $this->faker->numberBetween(0, $totalRecords);
        $failedRecords = $this->faker->numberBetween(0, $totalRecords - $processedRecords);

        return [
            'type' => $this->faker->randomElement(BulkOperationType::cases()),
            'status' => $this->faker->randomElement(BulkOperationStatus::cases()),
            'model_type' => $this->faker->randomElement([
                \App\Models\Company::class,
                \App\Models\People::class,
                \App\Models\Task::class,
                \App\Models\Note::class,
                \App\Models\Opportunity::class,
                \App\Models\SupportCase::class,
            ]),
            'total_records' => $totalRecords,
            'processed_records' => $processedRecords,
            'failed_records' => $failedRecords,
            'batch_size' => $this->faker->randomElement([50, 100, 200]),
            'operation_data' => $this->faker->randomElement([
                ['status' => 'active'],
                ['assigned_to' => 1],
                ['priority' => 'high'],
                null,
            ]),
            'errors' => $failedRecords > 0 ? [
                'Failed to update record ID 123: Permission denied',
                'Failed to update record ID 456: Validation error',
            ] : null,
            'started_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'completed_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 week', 'now'),
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BulkOperationStatus::PENDING,
            'processed_records' => 0,
            'failed_records' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BulkOperationStatus::PROCESSING,
            'started_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BulkOperationStatus::COMPLETED,
            'processed_records' => $attributes['total_records'],
            'failed_records' => 0,
            'started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'completed_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'errors' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BulkOperationStatus::FAILED,
            'failed_records' => $this->faker->numberBetween(1, $attributes['total_records']),
            'started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'completed_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'errors' => [
                'Database connection failed',
                'Permission denied for bulk operation',
                'Validation failed for multiple records',
            ],
        ]);
    }

    public function update(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => BulkOperationType::UPDATE,
            'operation_data' => [
                'status' => 'active',
                'priority' => 'high',
            ],
        ]);
    }

    public function delete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => BulkOperationType::DELETE,
            'operation_data' => null,
        ]);
    }

    public function assign(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => BulkOperationType::ASSIGN,
            'operation_data' => [
                'assigned_to' => $this->faker->numberBetween(1, 10),
            ],
        ]);
    }
}
