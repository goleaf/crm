<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CalendarSyncStatus: string implements HasColor, HasLabel
{
    case NOT_SYNCED = 'not_synced';
    case SYNCED = 'synced';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_SYNCED => __('enums.calendar_sync_status.not_synced'),
            self::SYNCED => __('enums.calendar_sync_status.synced'),
            self::FAILED => __('enums.calendar_sync_status.failed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NOT_SYNCED => 'gray',
            self::SYNCED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
