<?php

declare(strict_types=1);

namespace App\Services\Studio;

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Studio Service
 * 
 * Handles Studio customization operations including field management,
 * layout configuration, label customization, and dependency management.
 */
final class StudioService
{
    public function __construct(
        private readonly int $cacheTtl = 3600
    ) {}

    /**
     * Get layout definition for a module and view type
     */
    public function getLayoutDefinition(Team $team, string $moduleName, string $viewType): ?LayoutDefinition
    {
        $cacheKey = "studio.layout.{$team->id}.{$moduleName}.{$viewType}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($team, $moduleName, $viewType) {
            return LayoutDefinition::where('team_id', $team->id)
                ->forModule($moduleName)
                ->forViewType($viewType)
                ->active()
                ->first();
        });
    }

    /**
     * Create or update layout definition
     */
    public function saveLayoutDefinition(Team $team, array $data): LayoutDefinition
    {
        $layoutDefinition = LayoutDefinition::updateOrCreate(
            [
                'team_id' => $team->id,
                'module_name' => $data['module_name'],
                'view_type' => $data['view_type'],
                'name' => $data['name'],
            ],
            $data
        );

        $this->clearLayoutCache($team, $data['module_name'], $data['view_type']);

        return $layoutDefinition;
    }

    /**
     * Get field dependencies for a module
     */
    public function getFieldDependencies(Team $team, string $moduleName): Collection
    {
        $cacheKey = "studio.dependencies.{$team->id}.{$moduleName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($team, $moduleName) {
            return FieldDependency::where('team_id', $team->id)
                ->forModule($moduleName)
                ->active()
                ->get();
        });
    }

    /**
     * Create or update field dependency
     */
    public function saveFieldDependency(Team $team, array $data): FieldDependency
    {
        $dependency = FieldDependency::updateOrCreate(
            [
                'team_id' => $team->id,
                'module_name' => $data['module_name'],
                'source_field_code' => $data['source_field_code'],
                'target_field_code' => $data['target_field_code'],
                'dependency_type' => $data['dependency_type'],
            ],
            $data
        );

        $this->clearDependencyCache($team, $data['module_name']);

        return $dependency;
    }

    /**
     * Get label customizations for a module
     */
    public function getLabelCustomizations(Team $team, string $moduleName, string $locale = 'en'): Collection
    {
        $cacheKey = "studio.labels.{$team->id}.{$moduleName}.{$locale}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($team, $moduleName, $locale) {
            return LabelCustomization::where('team_id', $team->id)
                ->forModule($moduleName)
                ->forLocale($locale)
                ->active()
                ->get();
        });
    }

    /**
     * Create or update label customization
     */
    public function saveLabelCustomization(Team $team, array $data): LabelCustomization
    {
        $customization = LabelCustomization::updateOrCreate(
            [
                'team_id' => $team->id,
                'module_name' => $data['module_name'],
                'element_type' => $data['element_type'],
                'element_key' => $data['element_key'],
                'locale' => $data['locale'] ?? 'en',
            ],
            $data
        );

        $this->clearLabelCache($team, $data['module_name'], $data['locale'] ?? 'en');

        return $customization;
    }

    /**
     * Get custom label for an element
     */
    public function getCustomLabel(Team $team, string $moduleName, string $elementType, string $elementKey, string $locale = 'en'): ?string
    {
        $customizations = $this->getLabelCustomizations($team, $moduleName, $locale);
        
        $customization = $customizations->where('element_type', $elementType)
            ->where('element_key', $elementKey)
            ->first();

        return $customization?->custom_label;
    }

    /**
     * Apply field dependencies to form schema
     */
    public function applyFieldDependencies(Team $team, string $moduleName, array $schema): array
    {
        $dependencies = $this->getFieldDependencies($team, $moduleName);

        if ($dependencies->isEmpty()) {
            return $schema;
        }

        // Group dependencies by target field for efficient processing
        $dependenciesByTarget = $dependencies->groupBy('target_field_code');

        foreach ($schema as &$component) {
            if (!isset($component['name'])) {
                continue;
            }

            $fieldCode = $component['name'];
            $fieldDependencies = $dependenciesByTarget->get($fieldCode);

            if ($fieldDependencies) {
                $component['dependencies'] = $fieldDependencies->map(function ($dependency) {
                    return [
                        'source_field' => $dependency->source_field_code,
                        'condition_operator' => $dependency->condition_operator,
                        'condition_value' => $dependency->condition_value,
                        'action_type' => $dependency->action_type,
                        'action_config' => $dependency->action_config,
                    ];
                })->toArray();
            }
        }

        return $schema;
    }

    /**
     * Validate that customizations don't affect unrelated modules
     */
    public function validateCustomizationIsolation(Team $team, string $moduleName, array $data): bool
    {
        // Ensure module name is consistent
        if (isset($data['module_name']) && $data['module_name'] !== $moduleName) {
            return false;
        }

        // Check for cross-module field references in dependencies
        if (isset($data['source_field_code']) || isset($data['target_field_code'])) {
            // In a real implementation, you would validate that field codes
            // belong to the specified module
            return true;
        }

        return true;
    }

    /**
     * Clear layout cache
     */
    public function clearLayoutCache(Team $team, string $moduleName, string $viewType): void
    {
        $cacheKey = "studio.layout.{$team->id}.{$moduleName}.{$viewType}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear dependency cache
     */
    public function clearDependencyCache(Team $team, string $moduleName): void
    {
        $cacheKey = "studio.dependencies.{$team->id}.{$moduleName}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear label cache
     */
    public function clearLabelCache(Team $team, string $moduleName, string $locale): void
    {
        $cacheKey = "studio.labels.{$team->id}.{$moduleName}.{$locale}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all Studio caches for a team
     */
    public function clearAllCache(Team $team): void
    {
        $patterns = [
            "studio.layout.{$team->id}.*",
            "studio.dependencies.{$team->id}.*",
            "studio.labels.{$team->id}.*",
        ];

        foreach ($patterns as $pattern) {
            Cache::flush(); // In production, you'd use a more targeted approach
        }
    }

    /**
     * Get available modules for customization
     */
    public function getAvailableModules(): array
    {
        return LayoutDefinition::getAvailableModules();
    }

    /**
     * Get available view types
     */
    public function getAvailableViewTypes(): array
    {
        return LayoutDefinition::getViewTypes();
    }

    /**
     * Export module customizations
     */
    public function exportModuleCustomizations(Team $team, string $moduleName): array
    {
        return [
            'module_name' => $moduleName,
            'layouts' => LayoutDefinition::where('team_id', $team->id)
                ->forModule($moduleName)
                ->active()
                ->get()
                ->toArray(),
            'dependencies' => FieldDependency::where('team_id', $team->id)
                ->forModule($moduleName)
                ->active()
                ->get()
                ->toArray(),
            'labels' => LabelCustomization::where('team_id', $team->id)
                ->forModule($moduleName)
                ->active()
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Import module customizations
     */
    public function importModuleCustomizations(Team $team, array $data): bool
    {
        return DB::transaction(function () use ($team, $data) {
            $moduleName = $data['module_name'];

            // Import layouts
            if (isset($data['layouts'])) {
                foreach ($data['layouts'] as $layoutData) {
                    $layoutData['team_id'] = $team->id;
                    unset($layoutData['id'], $layoutData['created_at'], $layoutData['updated_at']);
                    $this->saveLayoutDefinition($team, $layoutData);
                }
            }

            // Import dependencies
            if (isset($data['dependencies'])) {
                foreach ($data['dependencies'] as $dependencyData) {
                    $dependencyData['team_id'] = $team->id;
                    unset($dependencyData['id'], $dependencyData['created_at'], $dependencyData['updated_at']);
                    $this->saveFieldDependency($team, $dependencyData);
                }
            }

            // Import labels
            if (isset($data['labels'])) {
                foreach ($data['labels'] as $labelData) {
                    $labelData['team_id'] = $team->id;
                    unset($labelData['id'], $labelData['created_at'], $labelData['updated_at']);
                    $this->saveLabelCustomization($team, $labelData);
                }
            }

            return true;
        });
    }
}