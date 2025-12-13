<?php

declare(strict_types=1);

namespace Database\Factories\Studio;

use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Studio\LabelCustomization>
 */
final class LabelCustomizationFactory extends Factory
{
    protected $model = LabelCustomization::class;

    public function definition(): array
    {
        $modules = array_keys(LayoutDefinition::getAvailableModules());
        $elementTypes = array_keys(LabelCustomization::getElementTypes());
        $locales = ['en', 'uk', 'ru', 'lt'];

        $elementKeys = [
            'field' => ['name', 'email', 'phone', 'status', 'type', 'category'],
            'module' => ['companies', 'people', 'opportunities', 'tasks'],
            'action' => ['create', 'edit', 'delete', 'view', 'export'],
            'navigation' => ['dashboard', 'workspace', 'settings'],
            'tab' => ['general', 'advanced', 'permissions'],
            'section' => ['basic_info', 'contact_details', 'preferences'],
        ];

        $elementType = $this->faker->randomElement($elementTypes);
        $elementKey = $this->faker->randomElement($elementKeys[$elementType] ?? ['generic_element']);

        return [
            'team_id' => Team::factory(),
            'module_name' => $this->faker->randomElement($modules),
            'element_type' => $elementType,
            'element_key' => $elementKey,
            'original_label' => $this->faker->words(2, true),
            'custom_label' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'locale' => $this->faker->randomElement($locales),
            'active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the label customization is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the label customization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set the module name for the label customization.
     */
    public function forModule(string $moduleName): static
    {
        return $this->state(fn (array $attributes) => [
            'module_name' => $moduleName,
        ]);
    }

    /**
     * Set the element type and key.
     */
    public function forElement(string $elementType, string $elementKey): static
    {
        return $this->state(fn (array $attributes) => [
            'element_type' => $elementType,
            'element_key' => $elementKey,
        ]);
    }

    /**
     * Set the locale.
     */
    public function forLocale(string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => $locale,
        ]);
    }

    /**
     * Set the labels.
     */
    public function withLabels(string $originalLabel, string $customLabel): static
    {
        return $this->state(fn (array $attributes) => [
            'original_label' => $originalLabel,
            'custom_label' => $customLabel,
        ]);
    }
}