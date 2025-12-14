<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportJob;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportJob>
 */
final class ImportJobFactory extends Factory
{
    protected $model = ImportJob::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Import',
            'type' => $this->faker->randomElement(['csv', 'xlsx', 'xls', 'vcard']),
            'model_type' => $this->faker->randomElement(['Company', 'People', 'Contact', 'Lead', 'Opportunity']),
            'file_path' => 'imports/' . $this->faker->uuid() . '.csv',
            'original_filename' => $this->faker->word() . '.csv',
            'file_size' => $this->faker->numberBetween(1024, 1048576), // 1KB to 1MB
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'mapping' => null,
            'duplicate_rules' => null,
            'validation_rules' => null,
            'total_rows' => $this->faker->numberBetween(10, 1000),
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'errors' => null,
            'preview_data' => null,
            'statistics' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'processing',
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $totalRows = $attributes['total_rows'] ?? $this->faker->numberBetween(10, 1000);
            $successfulRows = $this->faker->numberBetween(0, $totalRows);
            $failedRows = $this->faker->numberBetween(0, $totalRows - $successfulRows);
            $duplicateRows = $totalRows - $successfulRows - $failedRows;

            return [
                'status' => 'completed',
                'processed_rows' => $totalRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
                'duplicate_rows' => $duplicateRows,
                'started_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
                'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'errors' => [
                [
                    'row' => 1,
                    'errors' => ['Validation failed'],
                    'data' => ['name' => '', 'email' => 'invalid'],
                ],
            ],
        ]);
    }

    public function withMapping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mapping' => [
                'name' => 'company_name',
                'email' => 'email_address',
                'phone' => 'phone_number',
            ],
        ]);
    }

    public function withDuplicateRules(): static
    {
        return $this->state(fn (array $attributes): array => [
            'duplicate_rules' => [
                [
                    'name' => 'Email Match',
                    'fields' => ['email'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Name Match',
                    'fields' => ['name'],
                    'match_type' => 'exact',
                ],
            ],
        ]);
    }

    public function withPreviewData(): static
    {
        return $this->state(fn (array $attributes): array => [
            'preview_data' => [
                'headers' => ['name', 'email', 'phone'],
                'data' => [
                    [
                        'name' => $this->faker->company(),
                        'email' => $this->faker->companyEmail(),
                        'phone' => $this->faker->phoneNumber(),
                    ],
                    [
                        'name' => $this->faker->company(),
                        'email' => $this->faker->companyEmail(),
                        'phone' => $this->faker->phoneNumber(),
                    ],
                ],
                'total_rows' => $attributes['total_rows'] ?? 100,
            ],
        ]);
    }
}
