<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountTeamRole: string implements HasColor, HasLabel
{
    case OWNER = 'owner';
    case ACCOUNT_MANAGER = 'account_manager';
    case SALES = 'sales';
    case CUSTOMER_SUCCESS = 'customer_success';
    case EXECUTIVE_SPONSOR = 'executive_sponsor';
    case TECHNICAL_CONTACT = 'technical_contact';
    case BILLING = 'billing';
    case SUPPORT = 'support';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => __('enums.account_team_role.owner'),
            self::ACCOUNT_MANAGER => __('enums.account_team_role.account_manager'),
            self::SALES => __('enums.account_team_role.sales'),
            self::CUSTOMER_SUCCESS => __('enums.account_team_role.customer_success'),
            self::EXECUTIVE_SPONSOR => __('enums.account_team_role.executive_sponsor'),
            self::TECHNICAL_CONTACT => __('enums.account_team_role.technical_contact'),
            self::BILLING => __('enums.account_team_role.billing'),
            self::SUPPORT => __('enums.account_team_role.support'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OWNER => 'primary',
            self::ACCOUNT_MANAGER => 'info',
            self::SALES => 'success',
            self::CUSTOMER_SUCCESS => 'success',
            self::EXECUTIVE_SPONSOR => 'warning',
            self::TECHNICAL_CONTACT => 'gray',
            self::BILLING => 'gray',
            self::SUPPORT => 'gray',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->getLabel();
        }

        return $options;
    }
}
