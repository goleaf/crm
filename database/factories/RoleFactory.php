<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(2),
            'display_name' => $this->faker->jobTitle(),
            'description' => $this->faker->sentence(),
            'guard_name' => 'web',
            'is_template' => $this->faker->boolean(20), // 20% chance of being a template
            'is_admin_role' => $this->faker->boolean(10), // 10% chance of being admin
            'is_studio_role' => $this->faker->boolean(15), // 15% chance of being studio
            'metadata' => $this->faker->optional()->randomElement([
                ['department' => 'Sales'],
                ['level' => 'Senior'],
                ['region' => 'North America'],
                null,
            ]),
        ];
    }

    public function template(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_template' => true,
            'name' => $attributes['name'] . '_template',
            'display_name' => $attributes['display_name'] . ' Template',
        ]);
    }

    public function adminRole(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin_role' => true,
            'is_template' => false,
        ]);
    }

    public function studioRole(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_studio_role' => true,
            'is_template' => false,
        ]);
    }

    public function withParent(Role $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_role_id' => $parent->id,
        ]);
    }

    public function forTeam(int $teamId): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_id' => $teamId,
        ]);
    }
}
