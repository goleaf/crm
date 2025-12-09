<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Filament\Forms\Components\Tabs;

/**
 * MinimalTabs provides a cleaner, more compact tab interface for Filament v4.3+ forms.
 *
 * This component extends the standard Tabs component with a minimal styling approach
 * that reduces visual clutter and provides a more streamlined user experience.
 *
 * Features:
 * - Compact tab headers with reduced padding
 * - Cleaner visual separation between tabs
 * - Optional icon support
 * - Badge support for tab counts
 * - Responsive design
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
 * ```
 *
 * @see \Filament\Forms\Components\Tabs
 */
final class MinimalTabs extends Tabs
{
    protected string $view = 'filament.components.minimal-tabs';

    /**
     * Apply minimal styling to the tabs.
     */
    public function minimal(bool $condition = true): static
    {
        $this->extraAttributes([
            'class' => $condition ? 'minimal-tabs' : '',
        ]);

        return $this;
    }

    /**
     * Make tabs compact with reduced spacing.
     */
    public function compact(bool $condition = true): static
    {
        $this->extraAttributes([
            'class' => $condition ? 'minimal-tabs-compact' : '',
        ]);

        return $this;
    }

    /**
     * Create a new minimal tabs instance with default minimal styling.
     */
    public static function make(?string $label = null): static
    {
        return parent::make($label)->minimal();
    }
}
