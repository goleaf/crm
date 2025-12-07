<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExtensionStatus;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;
use App\Models\Extension;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extension>
 */
final class ExtensionFactory extends Factory
{
    protected $model = Extension::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(ExtensionType::cases()),
            'status' => ExtensionStatus::INACTIVE,
            'version' => '1.0.0',
            'priority' => fake()->numberBetween(1, 200),
            'target_model' => null,
            'target_event' => null,
            'handler_class' => \App\Extensions\TestHandler::class,
            'handler_method' => 'handle',
            'configuration' => [],
            'permissions' => [],
            'metadata' => [],
            'execution_count' => 0,
            'failure_count' => 0,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExtensionStatus::ACTIVE,
        ]);
    }

    public function logicHook(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ExtensionType::LOGIC_HOOK,
            'target_model' => \App\Models\Company::class,
            'target_event' => fake()->randomElement(HookEvent::cases()),
        ]);
    }

    public function withExecutions(int $count = 5, int $failures = 0): self
    {
        return $this->state(fn (array $attributes): array => [
            'execution_count' => $count,
            'failure_count' => $failures,
            'last_executed_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }
}
