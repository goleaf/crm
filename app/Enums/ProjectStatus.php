<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Project status enumeration.
 *
 * Represents the lifecycle states of a project from planning through completion or cancellation.
 *
 * Enhanced with BenSampo Laravel Enum helpers for validation, array conversion, and more.
 */
enum ProjectStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get the translated label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PLANNING => __('enums.project_status.planning'),
            self::ACTIVE => __('enums.project_status.active'),
            self::ON_HOLD => __('enums.project_status.on_hold'),
            self::COMPLETED => __('enums.project_status.completed'),
            self::CANCELLED => __('enums.project_status.cancelled'),
        };
    }

    /**
     * Get the Filament color for the status.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PLANNING => 'gray',
            self::ACTIVE => 'primary',
            self::ON_HOLD => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get the icon for the status.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PLANNING => 'heroicon-o-clipboard-document-list',
            self::ACTIVE => 'heroicon-o-play',
            self::ON_HOLD => 'heroicon-o-pause',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get all status options as an associative array for select fields.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }

        return $options;
    }

    /**
     * Check if the status represents an active project.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the status represents a completed project.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if the status represents a terminal state (completed or cancelled).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    /**
     * Check if the status allows project modifications.
     */
    public function allowsModifications(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * Get statuses that can be transitioned to from the current status.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PLANNING => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::ON_HOLD, self::COMPLETED, self::CANCELLED],
            self::ON_HOLD => [self::ACTIVE, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if transition to another status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
