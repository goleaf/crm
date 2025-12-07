<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum LeadNurtureStatus: string implements HasColor, HasLabel
{
    case NOT_STARTED = 'not_started';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $key = match ($this) {
            self::NOT_STARTED => 'enums.lead_nurture_status.not_started',
            self::ACTIVE => 'enums.lead_nurture_status.active',
            self::PAUSED => 'enums.lead_nurture_status.paused',
            self::COMPLETED => 'enums.lead_nurture_status.completed',
        };

        $label = __($key);

        return $label === $key ? Str::headline(str_replace('_', ' ', $this->value)) : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::ACTIVE => 'primary',
            self::PAUSED => 'warning',
            self::COMPLETED => 'success',
        };
    }

    /**
     * @return string|array<string>|null
     */
    public function color(): string|array|null
    {
        return $this->getColor();
    }

    /**
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
}
