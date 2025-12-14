<?php

declare(strict_types=1);

namespace Database\Factories\Studio;

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Studio\FieldDependency>
 */
final class FieldDependencyFactory extends Factory
{
    protected $model = FieldDependency::class;

    public function definition(): array
    {
        $modules = array_keys(LayoutDefinition::getAvailableModules());
        $dependencyTypes = array_keys(FieldDependency::getDependencyTypes());
        $conditionOperators = array_keys(FieldDependency::getConditionOperators());
        $actionTypes = array_keys(FieldDependency::getActionTypes());

        $fieldNames = ['status', 'type', 'category', 'priority', 'reason', 'department', 'role', 'level'];

        return [
            'team_id' => Team::factory(),
            'module_name' => $this->faker->randomElement($modules),
            'source_field_code' => $this->faker->randomElement($fieldNames),
            'target_field_code' => $this->faker->randomElement($fieldNames),
            'dependency_type' => $this->faker->randomElement($dependencyTypes),
            'condition_operator' => $this->faker->randomElement($conditionOperators),
            'condition_value' => [
                'value' => $this->faker->randomElement(['active', 'inactive', 'pending', 'completed']),
            ],
            'action_type' => $this->faker->randomElement($actionTypes),
            'action_config' => [
                'animation' => $this->faker->randomElement(['fade', 'slide', 'none']),
                'duration' => $this->faker->numberBetween(100, 500),
            ],
            'active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the field dependency is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the field dependency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }

    /**
     * Set the module name for the field dependency.
     */
    public function forModule(string $moduleName): static
    {
        return $this->state(fn (array $attributes): array => [
            'module_name' => $moduleName,
        ]);
    }

    /**
     * Set the dependency type.
     */
    public function ofType(string $dependencyType): static
    {
        return $this->state(fn (array $attributes): array => [
            'dependency_type' => $dependencyType,
        ]);
    }

    /**
     * Set the source and target fields.
     */
    public function withFields(string $sourceField, string $targetField): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_field_code' => $sourceField,
            'target_field_code' => $targetField,
        ]);
    }

    /**
     * Set the condition.
     */
    public function withCondition(string $operator, mixed $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'condition_operator' => $operator,
            'condition_value' => is_array($value) ? $value : ['value' => $value],
        ]);
    }
}
