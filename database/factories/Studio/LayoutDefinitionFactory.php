<?php

declare(strict_types=1);

namespace Database\Factories\Studio;

use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Studio\LayoutDefinition>
 */
final class LayoutDefinitionFactory extends Factory
{
    protected $model = LayoutDefinition::class;

    public function definition(): array
    {
        $modules = array_keys(LayoutDefinition::getAvailableModules());
        $viewTypes = array_keys(LayoutDefinition::getViewTypes());

        return [
            'team_id' => Team::factory(),
            'module_name' => $this->faker->randomElement($modules),
            'view_type' => $this->faker->randomElement($viewTypes),
            'name' => $this->faker->words(3, true) . ' Layout',
            'description' => $this->faker->sentence(),
            'components' => [
                'field_' . $this->faker->word => [
                    'type' => $this->faker->randomElement(['text', 'select', 'textarea']),
                    'label' => $this->faker->words(2, true),
                    'required' => $this->faker->boolean(),
                ],
                'field_' . $this->faker->word => [
                    'type' => $this->faker->randomElement(['number', 'date', 'email']),
                    'label' => $this->faker->words(2, true),
                    'required' => $this->faker->boolean(),
                ],
            ],
            'ordering' => [
                'field_1' => 1,
                'field_2' => 2,
                'field_3' => 3,
            ],
            'visibility_rules' => [
                'condition_1' => [
                    'field' => 'status',
                    'operator' => 'equals',
                    'value' => 'active',
                ],
            ],
            'group_overrides' => [
                'admin' => [
                    'show_advanced_fields' => true,
                ],
                'user' => [
                    'show_advanced_fields' => false,
                ],
            ],
            'active' => $this->faker->boolean(90), // 90% chance of being active
            'system_defined' => $this->faker->boolean(10), // 10% chance of being system defined
        ];
    }

    /**
     * Indicate that the layout definition is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the layout definition is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the layout definition is system defined.
     */
    public function systemDefined(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_defined' => true,
        ]);
    }

    /**
     * Indicate that the layout definition is user defined.
     */
    public function userDefined(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_defined' => false,
        ]);
    }

    /**
     * Set the module name for the layout definition.
     */
    public function forModule(string $moduleName): static
    {
        return $this->state(fn (array $attributes) => [
            'module_name' => $moduleName,
        ]);
    }

    /**
     * Set the view type for the layout definition.
     */
    public function forViewType(string $viewType): static
    {
        return $this->state(fn (array $attributes) => [
            'view_type' => $viewType,
        ]);
    }
}