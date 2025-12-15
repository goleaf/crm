<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcessStepStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.process_step_status.pending'),
            self::IN_PROGRESS => __('enums.process_step_status.in_progress'),
            self::COMPLETED => __('enums.process_step_status.completed'),
            self::SKIPPED => __('enums.process_step_status.skipped'),
            self::FAILED => __('enums.process_step_status.failed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::SKIPPED => 'warning',
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
