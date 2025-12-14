<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MergeJobType: string implements HasColor, HasIcon, HasLabel
{
    case COMPANY = 'company';
    case CONTACT = 'contact';
    case LEAD = 'lead';
    case OPPORTUNITY = 'opportunity';
    case ACCOUNT = 'account';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY => __('enums.merge_job_type.company'),
            self::CONTACT => __('enums.merge_job_type.contact'),
            self::LEAD => __('enums.merge_job_type.lead'),
            self::OPPORTUNITY => __('enums.merge_job_type.opportunity'),
            self::ACCOUNT => __('enums.merge_job_type.account'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::COMPANY => 'blue',
            self::CONTACT => 'green',
            self::LEAD => 'yellow',
            self::OPPORTUNITY => 'purple',
            self::ACCOUNT => 'indigo',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::COMPANY => 'heroicon-o-building-office',
            self::CONTACT => 'heroicon-o-user',
            self::LEAD => 'heroicon-o-user-plus',
            self::OPPORTUNITY => 'heroicon-o-currency-dollar',
            self::ACCOUNT => 'heroicon-o-building-office-2',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
