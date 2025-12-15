<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Extension;
use App\Models\ExtensionExecution;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtensionExecution>
 */
final class ExtensionExecutionFactory extends Factory
{
    protected $model = ExtensionExecution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'extension_id' => Extension::factory(),
            'user_id' => User::factory(),
            'status' => 'success',
            'input_data' => ['test' => 'data'],
            'output_data' => ['result' => 'success'],
            'error_message' => null,
            'execution_time_ms' => fake()->numberBetween(10, 500),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'output_data' => null,
            'error_message' => fake()->sentence(),
            'execution_time_ms' => fake()->numberBetween(10, 100),
        ]);
    }
}
