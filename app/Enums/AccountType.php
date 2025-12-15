<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum AccountType: string implements HasColor, HasLabel
{
    case CUSTOMER = 'customer';
    case PROSPECT = 'prospect';
    case PARTNER = 'partner';
    case COMPETITOR = 'competitor';
    case INVESTOR = 'investor';
    case RESELLER = 'reseller';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $key = match ($this) {
            self::CUSTOMER => 'enums.account_type.customer',
            self::PROSPECT => 'enums.account_type.prospect',
            self::PARTNER => 'enums.account_type.partner',
            self::COMPETITOR => 'enums.account_type.competitor',
            self::INVESTOR => 'enums.account_type.investor',
            self::RESELLER => 'enums.account_type.reseller',
        };

        $label = __($key);

        return $label === $key ? Str::headline($this->value) : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CUSTOMER => 'success',
            self::PROSPECT => 'warning',
            self::PARTNER => 'info',
            self::COMPETITOR => 'danger',
            self::INVESTOR => 'primary',
            self::RESELLER => 'gray',
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

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->getLabel();
        }

        return $options;
    }
}
