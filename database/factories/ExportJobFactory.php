<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExportJob;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportJob>
 */
final class ExportJobFactory extends Factory
{
    protected $model = ExportJob::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Export',
            'model_type' => $this->faker->randomElement(['Company', 'People', 'Opportunity', 'Task', 'Note']),
            'format' => $this->faker->randomElement(['csv', 'xlsx']),
            'template_config' => null,
            'selected_fields' => ['id', 'name', 'email', 'created_at'],
            'filters' => null,
            'options' => null,
            'scope' => $this->faker->randomElement(['all', 'filtered', 'selected']),
            'record_ids' => null,
            'status' => 'pending',
            'total_records' => 0,
            'processed_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'file_path' => null,
            'file_disk' => 'local',
            'file_size' => null,
            'expires_at' => now()->addDays(7),
            'errors' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $totalRecords = $this->faker->numberBetween(10, 1000);
            $successfulRecords = $this->faker->numberBetween(8, $totalRecords);

            return [
                'status' => 'completed',
                'total_records' => $totalRecords,
                'processed_records' => $totalRecords,
                'successful_records' => $successfulRecords,
                'failed_records' => $totalRecords - $successfulRecords,
                'file_path' => 'exports/' . $this->faker->uuid() . '.csv',
                'file_size' => $this->faker->numberBetween(1024, 1048576), // 1KB to 1MB
                'started_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
                'completed_at' => now(),
            ];
        });
    }

    public function processing(): static
    {
        return $this->state(function (array $attributes): array {
            $totalRecords = $this->faker->numberBetween(100, 1000);
            $processedRecords = $this->faker->numberBetween(10, $totalRecords - 10);

            return [
                'status' => 'processing',
                'total_records' => $totalRecords,
                'processed_records' => $processedRecords,
                'successful_records' => $processedRecords,
                'failed_records' => 0,
                'started_at' => now()->subMinutes($this->faker->numberBetween(1, 30)),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'errors' => ['general' => [$this->faker->sentence()]],
            'started_at' => now()->subMinutes($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function withTemplate(): static
    {
        return $this->state([
            'template_config' => [
                'name' => $this->faker->words(2, true) . ' Template',
                'description' => $this->faker->sentence(),
                'fields' => ['id', 'name', 'email', 'phone', 'created_at'],
                'format_options' => [
                    'date_format' => 'Y-m-d',
                    'include_headers' => true,
                    'delimiter' => ',',
                ],
            ],
        ]);
    }

    public function withFilters(): static
    {
        return $this->state([
            'filters' => [
                'created_at' => [
                    'from' => now()->subMonths(3)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'status' => 'active',
            ],
        ]);
    }

    public function withSelectedRecords(): static
    {
        return $this->state([
            'scope' => 'selected',
            'record_ids' => $this->faker->randomElements(range(1, 100), $this->faker->numberBetween(5, 20)),
        ]);
    }
}
