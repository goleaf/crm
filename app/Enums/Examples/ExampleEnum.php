<?php

declare(strict_types=1);

namespace App\Enums\Examples;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Example enum demonstrating BenSampo Laravel Enum integration.
 *
 * This serves as a template for creating new enums with full Filament support.
 */
enum ExampleEnum: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get the translated label for the enum case.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('app.example_enum.draft'),
            self::PUBLISHED => __('app.example_enum.published'),
            self::ARCHIVED => __('app.example_enum.archived'),
        };
    }

    /**
     * Get the Filament color for the enum case.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'warning',
        };
    }

    /**
     * Get the icon for the enum case.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::PUBLISHED => 'heroicon-o-check-circle',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    /**
     * Check if the status is published.
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if the status allows editing.
     */
    public function allowsEditing(): bool
    {
        return $this !== self::ARCHIVED;
    }
}
