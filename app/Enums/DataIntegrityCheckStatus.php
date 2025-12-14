<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DataIntegrityCheckStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.data_integrity_check_status.pending'),
            self::RUNNING => __('enums.data_integrity_check_status.running'),
            self::COMPLETED => __('enums.data_integrity_check_status.completed'),
            self::FAILED => __('enums.data_integrity_check_status.failed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::RUNNING => 'heroicon-o-arrow-path',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
