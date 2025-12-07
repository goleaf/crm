<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExtensionStatus: string implements HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISABLED = 'disabled';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('enums.extension_status.active'),
            self::INACTIVE => __('enums.extension_status.inactive'),
            self::DISABLED => __('enums.extension_status.disabled'),
            self::FAILED => __('enums.extension_status.failed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
