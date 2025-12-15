<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CaseChannel: string implements HasLabel
{
    case EMAIL = 'email';
    case PORTAL = 'portal';
    case PHONE = 'phone';
    case CHAT = 'chat';
    case INTERNAL = 'internal';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => __('enums.case_channel.email'),
            self::PORTAL => __('enums.case_channel.portal'),
            self::PHONE => __('enums.case_channel.phone'),
            self::CHAT => __('enums.case_channel.chat'),
            self::INTERNAL => __('enums.case_channel.internal'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
