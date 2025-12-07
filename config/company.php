<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\Industry;

return [
    'account_types' => [
        AccountType::CUSTOMER->value => 'enums.account_type.customer',
        AccountType::PROSPECT->value => 'enums.account_type.prospect',
        AccountType::PARTNER->value => 'enums.account_type.partner',
        AccountType::VENDOR->value => 'enums.account_type.vendor',
    ],

    'ownership_types' => [
        'public' => 'Public',
        'private' => 'Private',
        'subsidiary' => 'Subsidiary',
        'government' => 'Government',
        'non_profit' => 'Non-profit',
    ],

    'industries' => [
        Industry::AGRICULTURE->value => 'enums.industry.agriculture',
        Industry::AUTOMOTIVE->value => 'enums.industry.automotive',
        Industry::CONSTRUCTION->value => 'enums.industry.construction',
        Industry::CONSULTING->value => 'enums.industry.consulting',
        Industry::EDUCATION->value => 'enums.industry.education',
        Industry::ENERGY->value => 'enums.industry.energy',
        Industry::FINANCE->value => 'enums.industry.finance',
        Industry::GOVERNMENT->value => 'enums.industry.government',
        Industry::HEALTHCARE->value => 'enums.industry.healthcare',
        Industry::HOSPITALITY->value => 'enums.industry.hospitality',
        Industry::INSURANCE->value => 'enums.industry.insurance',
        Industry::LOGISTICS->value => 'enums.industry.logistics',
        Industry::MANUFACTURING->value => 'enums.industry.manufacturing',
        Industry::MEDIA->value => 'enums.industry.media',
        Industry::NON_PROFIT->value => 'enums.industry.non_profit',
        Industry::PROFESSIONAL_SERVICES->value => 'enums.industry.professional_services',
        Industry::REAL_ESTATE->value => 'enums.industry.real_estate',
        Industry::RENEWABLE_ENERGY->value => 'enums.industry.renewable_energy',
        Industry::RETAIL->value => 'enums.industry.retail',
        Industry::TECHNOLOGY->value => 'enums.industry.technology',
        Industry::TELECOMMUNICATIONS->value => 'enums.industry.telecommunications',
        Industry::TRANSPORTATION->value => 'enums.industry.transportation',
        Industry::OTHER->value => 'enums.industry.other',
    ],

    'currency_codes' => [
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'CAD' => 'CAD',
        'AUD' => 'AUD',
    ],

    'default_currency' => 'USD',
];
