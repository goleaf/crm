<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BackupJobStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.backup_job_status.pending'),
            self::RUNNING => __('enums.backup_job_status.running'),
            self::COMPLETED => __('enums.backup_job_status.completed'),
            self::FAILED => __('enums.backup_job_status.failed'),
            self::EXPIRED => __('enums.backup_job_status.expired'),
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
            self::EXPIRED => 'gray',
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
            self::EXPIRED => 'heroicon-o-archive-box-x-mark',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
