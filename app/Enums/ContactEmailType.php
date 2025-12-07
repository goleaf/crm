<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactEmailType: string implements HasLabel
{
    case Work = 'work';
    case Personal = 'personal';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Work => __('enums.contact_email_type.work'),
            self::Personal => __('enums.contact_email_type.personal'),
            self::Other => __('enums.contact_email_type.other'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
