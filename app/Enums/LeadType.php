<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum LeadType: string implements HasLabel
{
    case NEW_BUSINESS = 'new_business';
    case EXISTING_BUSINESS = 'existing_business';

    public function getLabel(): string
    {
        $key = match ($this) {
            self::NEW_BUSINESS => 'enums.lead_type.new_business',
            self::EXISTING_BUSINESS => 'enums.lead_type.existing_business',
        };

        $label = __($key);

        return $label === $key ? Str::headline($this->value) : $label;
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /**
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
}
