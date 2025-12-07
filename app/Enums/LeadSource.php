<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadSource: string implements HasLabel
{
    case WEBSITE = 'website';
    case WEB_FORM = 'web_form';
    case REFERRAL = 'referral';
    case PARTNER = 'partner';
    case CAMPAIGN = 'campaign';
    case EVENT = 'event';
    case ADVERTISING = 'advertising';
    case SOCIAL = 'social';
    case EMAIL = 'email';
    case COLD_CALL = 'cold_call';
    case OUTBOUND = 'outbound';
    case IMPORT = 'import';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::WEBSITE => __('enums.lead_source.website'),
            self::WEB_FORM => __('enums.lead_source.web_form'),
            self::REFERRAL => __('enums.lead_source.referral'),
            self::PARTNER => __('enums.lead_source.partner'),
            self::CAMPAIGN => __('enums.lead_source.campaign'),
            self::EVENT => __('enums.lead_source.event'),
            self::ADVERTISING => __('enums.lead_source.advertising'),
            self::SOCIAL => __('enums.lead_source.social'),
            self::EMAIL => __('enums.lead_source.email'),
            self::COLD_CALL => __('enums.lead_source.cold_call'),
            self::OUTBOUND => __('enums.lead_source.outbound'),
            self::IMPORT => __('enums.lead_source.import'),
            self::OTHER => __('enums.lead_source.other'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $source) {
            $options[$source->value] = $source->getLabel();
        }

        return $options;
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
