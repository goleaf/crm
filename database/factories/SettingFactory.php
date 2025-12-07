<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
final class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word().'.'.fake()->word(),
            'value' => fake()->sentence(),
            'type' => fake()->randomElement(['string', 'integer', 'boolean', 'json', 'array']),
            'group' => fake()->randomElement(['general', 'company', 'locale', 'email', 'scheduler', 'notification']),
            'description' => fake()->optional()->sentence(),
            'is_public' => fake()->boolean(20), // 20% chance of being public
            'is_encrypted' => fake()->boolean(10), // 10% chance of being encrypted
            'team_id' => null,
        ];
    }

    /**
     * Indicate that the setting is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the setting is encrypted.
     */
    public function encrypted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_encrypted' => true,
        ]);
    }

    /**
     * Indicate that the setting belongs to a team.
     */
    public function forTeam(?Team $team = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_id' => $team?->id ?? Team::factory(),
        ]);
    }

    /**
     * Create a string type setting.
     */
    public function string(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'string',
            'value' => fake()->sentence(),
        ]);
    }

    /**
     * Create an integer type setting.
     */
    public function integer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 1000),
        ]);
    }

    /**
     * Create a boolean type setting.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'boolean',
            'value' => fake()->boolean() ? '1' : '0',
        ]);
    }

    /**
     * Create a json type setting.
     */
    public function json(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'json',
            'value' => json_encode([
                'key1' => fake()->word(),
                'key2' => fake()->word(),
            ]),
        ]);
    }

    /**
     * Create an array type setting.
     */
    public function array(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'array',
            'value' => json_encode([
                fake()->word(),
                fake()->word(),
                fake()->word(),
            ]),
        ]);
    }

    /**
     * Create a setting in a specific group.
     */
    public function inGroup(string $group): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => $group,
        ]);
    }

    /**
     * Create a company group setting.
     */
    public function company(): static
    {
        return $this->inGroup('company');
    }

    /**
     * Create a locale group setting.
     */
    public function locale(): static
    {
        return $this->inGroup('locale');
    }

    /**
     * Create an email group setting.
     */
    public function email(): static
    {
        return $this->inGroup('email');
    }

    /**
     * Create a notification group setting.
     */
    public function notification(): static
    {
        return $this->inGroup('notification');
    }
}
