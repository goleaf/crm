<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BackupJobStatus;
use App\Enums\BackupJobType;
use App\Models\BackupJob;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupJob>
 */
final class BackupJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<BackupJob>
     */
    protected $model = BackupJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'type' => $this->faker->randomElement(BackupJobType::cases()),
            'status' => $this->faker->randomElement(BackupJobStatus::cases()),
            'name' => 'Backup ' . $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'description' => $this->faker->optional()->sentence(),
            'backup_config' => [
                'async' => $this->faker->boolean(),
                'retention_days' => $this->faker->numberBetween(7, 90),
                'files' => ['storage/app', '.env', 'composer.json'],
                'compress' => true,
            ],
            'backup_path' => $this->faker->optional()->filePath(),
            'file_size' => $this->faker->optional()->numberBetween(1024 * 1024, 1024 * 1024 * 100), // 1MB to 100MB
            'checksum' => $this->faker->optional()->sha256(),
            'verification_results' => [
                'file_exists' => true,
                'file_size' => $this->faker->numberBetween(1024 * 1024, 1024 * 1024 * 100),
                'checksum_valid' => true,
                'content_valid' => true,
                'errors' => [],
            ],
            'created_by' => User::factory(),
            'started_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+3 months'),
        ];
    }

    /**
     * Indicate that the backup job is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BackupJobStatus::PENDING,
            'started_at' => null,
            'completed_at' => null,
            'backup_path' => null,
            'file_size' => null,
            'checksum' => null,
            'verification_results' => null,
        ]);
    }

    /**
     * Indicate that the backup job is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BackupJobStatus::RUNNING,
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'backup_path' => null,
            'file_size' => null,
            'checksum' => null,
            'verification_results' => null,
        ]);
    }

    /**
     * Indicate that the backup job is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BackupJobStatus::COMPLETED,
            'started_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'backup_path' => storage_path('app/backups/backup_' . $this->faker->uuid() . '.tar.gz'),
            'file_size' => $this->faker->numberBetween(1024 * 1024, 1024 * 1024 * 100),
            'checksum' => $this->faker->sha256(),
        ]);
    }

    /**
     * Indicate that the backup job has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BackupJobStatus::FAILED,
            'error_message' => $this->faker->sentence(),
            'started_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'backup_path' => null,
            'file_size' => null,
            'checksum' => null,
            'verification_results' => null,
        ]);
    }

    /**
     * Indicate that the backup job is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BackupJobStatus::EXPIRED,
            'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}
