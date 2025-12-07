<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcessExecutionStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case ESCALATED = 'escalated';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case ROLLED_BACK = 'rolled_back';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.process_execution_status.pending'),
            self::IN_PROGRESS => __('enums.process_execution_status.in_progress'),
            self::AWAITING_APPROVAL => __('enums.process_execution_status.awaiting_approval'),
            self::ESCALATED => __('enums.process_execution_status.escalated'),
            self::COMPLETED => __('enums.process_execution_status.completed'),
            self::FAILED => __('enums.process_execution_status.failed'),
            self::CANCELLED => __('enums.process_execution_status.cancelled'),
            self::ROLLED_BACK => __('enums.process_execution_status.rolled_back'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'info',
            self::AWAITING_APPROVAL => 'warning',
            self::ESCALATED => 'danger',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
            self::ROLLED_BACK => 'warning',
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
