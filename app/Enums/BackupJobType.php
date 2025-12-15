<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BackupJobType: string implements HasColor, HasIcon, HasLabel
{
    case FULL = 'full';
    case INCREMENTAL = 'incremental';
    case DIFFERENTIAL = 'differential';
    case DATABASE_ONLY = 'database_only';
    case FILES_ONLY = 'files_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::FULL => __('enums.backup_job_type.full'),
            self::INCREMENTAL => __('enums.backup_job_type.incremental'),
            self::DIFFERENTIAL => __('enums.backup_job_type.differential'),
            self::DATABASE_ONLY => __('enums.backup_job_type.database_only'),
            self::FILES_ONLY => __('enums.backup_job_type.files_only'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FULL => 'blue',
            self::INCREMENTAL => 'green',
            self::DIFFERENTIAL => 'yellow',
            self::DATABASE_ONLY => 'purple',
            self::FILES_ONLY => 'orange',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::FULL => 'heroicon-o-archive-box',
            self::INCREMENTAL => 'heroicon-o-arrow-up-tray',
            self::DIFFERENTIAL => 'heroicon-o-arrow-path',
            self::DATABASE_ONLY => 'heroicon-o-circle-stack',
            self::FILES_ONLY => 'heroicon-o-folder',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
