<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum LeadStatus: string implements HasColor, HasLabel
{
    case NEW = 'new';
    case WORKING = 'working';
    case NURTURING = 'nurturing';
    case QUALIFIED = 'qualified';
    case UNQUALIFIED = 'unqualified';
    case CONVERTED = 'converted';
    case RECYCLED = 'recycled';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $key = match ($this) {
            self::NEW => 'enums.lead_status.new',
            self::WORKING => 'enums.lead_status.working',
            self::NURTURING => 'enums.lead_status.nurturing',
            self::QUALIFIED => 'enums.lead_status.qualified',
            self::UNQUALIFIED => 'enums.lead_status.unqualified',
            self::CONVERTED => 'enums.lead_status.converted',
            self::RECYCLED => 'enums.lead_status.recycled',
        };

        $label = __($key);

        return $label === $key ? Str::headline($this->value) : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'gray',
            self::WORKING => 'primary',
            self::NURTURING => 'info',
            self::QUALIFIED => 'success',
            self::UNQUALIFIED => 'danger',
            self::CONVERTED => 'success',
            self::RECYCLED => 'warning',
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
