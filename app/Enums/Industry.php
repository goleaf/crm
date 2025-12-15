<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Industry: string implements HasLabel
{
    case AGRICULTURE = 'agriculture';
    case AUTOMOTIVE = 'automotive';
    case CONSTRUCTION = 'construction';
    case CONSULTING = 'consulting';
    case EDUCATION = 'education';
    case ENERGY = 'energy';
    case FINANCE = 'finance';
    case GOVERNMENT = 'government';
    case HEALTHCARE = 'healthcare';
    case HOSPITALITY = 'hospitality';
    case INSURANCE = 'insurance';
    case LOGISTICS = 'logistics';
    case MANUFACTURING = 'manufacturing';
    case MEDIA = 'media';
    case NON_PROFIT = 'non_profit';
    case PROFESSIONAL_SERVICES = 'professional_services';
    case REAL_ESTATE = 'real_estate';
    case RENEWABLE_ENERGY = 'renewable_energy';
    case RETAIL = 'retail';
    case TECHNOLOGY = 'technology';
    case TELECOMMUNICATIONS = 'telecommunications';
    case TRANSPORTATION = 'transportation';
    case OTHER = 'other';

    public function getLabel(): string
    {
        $key = match ($this) {
            self::AGRICULTURE => 'enums.industry.agriculture',
            self::AUTOMOTIVE => 'enums.industry.automotive',
            self::CONSTRUCTION => 'enums.industry.construction',
            self::CONSULTING => 'enums.industry.consulting',
            self::EDUCATION => 'enums.industry.education',
            self::ENERGY => 'enums.industry.energy',
            self::FINANCE => 'enums.industry.finance',
            self::GOVERNMENT => 'enums.industry.government',
            self::HEALTHCARE => 'enums.industry.healthcare',
            self::HOSPITALITY => 'enums.industry.hospitality',
            self::INSURANCE => 'enums.industry.insurance',
            self::LOGISTICS => 'enums.industry.logistics',
            self::MANUFACTURING => 'enums.industry.manufacturing',
            self::MEDIA => 'enums.industry.media',
            self::NON_PROFIT => 'enums.industry.non_profit',
            self::PROFESSIONAL_SERVICES => 'enums.industry.professional_services',
            self::REAL_ESTATE => 'enums.industry.real_estate',
            self::RENEWABLE_ENERGY => 'enums.industry.renewable_energy',
            self::RETAIL => 'enums.industry.retail',
            self::TECHNOLOGY => 'enums.industry.technology',
            self::TELECOMMUNICATIONS => 'enums.industry.telecommunications',
            self::TRANSPORTATION => 'enums.industry.transportation',
            self::OTHER => 'enums.industry.other',
        };

        return app()->bound('translator') ? __($key) : $key;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $industry) {
            $options[$industry->value] = $industry->getLabel();
        }

        return $options;
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
