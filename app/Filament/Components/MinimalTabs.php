<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Closure;
use Filament\Schemas\Components\Tabs;
use Illuminate\Contracts\Support\Htmlable;

/**
 * MinimalTabs provides a cleaner, more compact tab interface for Filament v4.3+ forms.
 *
 * This component extends the standard Tabs component with a minimal styling approach
 * that reduces visual clutter and provides a more streamlined user experience.
 * 
 * **Filament v4.3+ Compatibility**: This component uses the unified schema system
 * introduced in Filament v4.3+, extending from `Filament\Schemas\Components\Tabs`
 * for full compatibility with the modern Filament architecture.
 *
 * Features:
 * - Compact tab headers with reduced padding
 * - Cleaner visual separation between tabs
 * - Optional icon support
 * - Badge support for tab counts
 * - Responsive design
 * - Proper CSS class management (additive, not overwriting)
 * - Full compatibility with Filament v4.3+ unified schema system
 *
 * Usage:
 * ```php
 * MinimalTabs::make('Settings')
 *     ->tabs([
 *         MinimalTabs\Tab::make('General')
 *             ->icon('heroicon-o-cog')
 *             ->badge('3')
 *             ->schema([...]),
 *         MinimalTabs\Tab::make('Advanced')
 *             ->schema([...]),
 *     ])
 *     ->compact() // Can be chained with minimal()
 * ```
 *
 * @see \Filament\Schemas\Components\Tabs
 * @since 1.0.0
 * @version 2.0.0 Filament v4.3+ unified schema compatibility
 */
final class MinimalTabs extends Tabs
{
    protected string $view = 'filament.components.minimal-tabs';

    /**
     * Apply minimal styling to the tabs.
     *
     * This method adds the 'minimal-tabs' CSS class to provide cleaner,
     * less cluttered tab styling. Can be combined with compact() for
     * even more streamlined appearance.
     *
     * @param bool $condition Whether to apply minimal styling
     * @return static The MinimalTabs instance for method chaining
     */
    public function minimal(bool $condition = true): static
    {
        if ($condition) {
            $this->addCssClass('minimal-tabs');
        } else {
            $this->removeCssClass('minimal-tabs');
        }

        return $this;
    }

    /**
     * Make tabs compact with reduced spacing.
     *
     * This method adds the 'minimal-tabs-compact' CSS class to provide
     * tighter spacing between tabs and reduced padding. Can be combined
     * with minimal() for maximum space efficiency.
     *
     * @param bool $condition Whether to apply compact styling
     * @return static The MinimalTabs instance for method chaining
     */
    public function compact(bool $condition = true): static
    {
        if ($condition) {
            $this->addCssClass('minimal-tabs-compact');
        } else {
            $this->removeCssClass('minimal-tabs-compact');
        }

        return $this;
    }

    /**
     * Create a new minimal tabs instance with default minimal styling.
     *
     * This factory method creates a new MinimalTabs instance and automatically
     * applies minimal styling. Additional styling can be added by chaining
     * methods like compact().
     *
     * @param Htmlable|Closure|string|null $label The label for the tabs
     * @return static A new MinimalTabs instance with minimal styling applied
     */
    public static function make(Htmlable|Closure|string|null $label = null): static
    {
        return parent::make($label)->minimal();
    }

    /**
     * Add a CSS class to the component's extra attributes.
     *
     * This method safely adds a CSS class without overwriting existing classes.
     * It handles the case where no class attribute exists yet.
     *
     * @param string $class The CSS class to add
     * @return void
     */
    private function addCssClass(string $class): void
    {
        $attributes = $this->getExtraAttributes();
        $existingClasses = $attributes['class'] ?? '';
        
        // Quick check if class already exists to avoid array operations
        if ($existingClasses !== '' && str_contains($existingClasses, $class)) {
            $pattern = '/\b' . preg_quote($class, '/') . '\b/';
            if (preg_match($pattern, $existingClasses)) {
                return; // Class already exists
            }
        }
        
        // Split existing classes and filter out empty strings
        $classes = array_filter(explode(' ', $existingClasses));
        
        // Add new class (we already checked it doesn't exist)
        $classes[] = $class;
        
        $this->extraAttributes([
            'class' => implode(' ', $classes),
        ]);
    }

    /**
     * Remove a CSS class from the component's extra attributes.
     *
     * This method safely removes a CSS class without affecting other classes.
     * If the class doesn't exist, no action is taken.
     *
     * @param string $class The CSS class to remove
     * @return void
     */
    private function removeCssClass(string $class): void
    {
        $attributes = $this->getExtraAttributes();
        $existingClasses = $attributes['class'] ?? '';
        
        // Quick check if class exists before expensive operations
        if ($existingClasses === '' || !str_contains($existingClasses, $class)) {
            return; // Class doesn't exist, nothing to remove
        }
        
        // Use regex for precise word boundary matching and replacement
        $pattern = '/\b' . preg_quote($class, '/') . '\b/';
        $newClasses = preg_replace($pattern, '', $existingClasses);
        
        // Clean up multiple spaces and trim
        $newClasses = trim(preg_replace('/\s+/', ' ', $newClasses));
        
        $this->extraAttributes([
            'class' => $newClasses,
        ]);
    }
}
