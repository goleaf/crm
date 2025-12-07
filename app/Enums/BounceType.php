<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Email bounce type enumeration.
 *
 * Represents different types of email delivery failures:
 * - HARD: Permanent delivery failure (invalid address, domain doesn't exist)
 * - SOFT: Temporary delivery failure (mailbox full, server temporarily unavailable)
 * - COMPLAINT: Recipient marked email as spam
 */
enum BounceType: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelpers;

    case HARD = 'hard';
    case SOFT = 'soft';
    case COMPLAINT = 'complaint';

    /**
     * Get the translated label for the bounce type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HARD => __('enums.bounce_type.hard'),
            self::SOFT => __('enums.bounce_type.soft'),
            self::COMPLAINT => __('enums.bounce_type.complaint'),
        };
    }

    /**
     * Get the Filament color for the bounce type.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::HARD => 'danger',
            self::SOFT => 'warning',
            self::COMPLAINT => 'danger',
        };
    }

    /**
     * Get the icon for the bounce type.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::HARD => 'heroicon-o-x-circle',
            self::SOFT => 'heroicon-o-exclamation-triangle',
            self::COMPLAINT => 'heroicon-o-flag',
        };
    }

    /**
     * Get all bounce type options as an associative array for select fields.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->getLabel();
        }

        return $options;
    }

    /**
     * Check if the bounce type is permanent (hard bounce or complaint).
     */
    public function isPermanent(): bool
    {
        return in_array($this, [self::HARD, self::COMPLAINT], true);
    }

    /**
     * Check if the bounce type is temporary (soft bounce).
     */
    public function isTemporary(): bool
    {
        return $this === self::SOFT;
    }

    /**
     * Check if the bounce type should suppress future emails.
     */
    public function shouldSuppressEmail(): bool
    {
        return $this->isPermanent();
    }

    /**
     * Get the severity level (1-3, where 3 is most severe).
     */
    public function getSeverity(): int
    {
        return match ($this) {
            self::HARD => 3,
            self::COMPLAINT => 3,
            self::SOFT => 1,
        };
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
