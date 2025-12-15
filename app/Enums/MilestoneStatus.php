<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MilestoneStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case READY_FOR_REVIEW = 'ready_for_review';
    case UNDER_REVIEW = 'under_review';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_STARTED => __('enums.milestone_status.not_started'),
            self::IN_PROGRESS => __('enums.milestone_status.in_progress'),
            self::READY_FOR_REVIEW => __('enums.milestone_status.ready_for_review'),
            self::UNDER_REVIEW => __('enums.milestone_status.under_review'),
            self::COMPLETED => __('enums.milestone_status.completed'),
            self::OVERDUE => __('enums.milestone_status.overdue'),
            self::BLOCKED => __('enums.milestone_status.blocked'),
            self::CANCELLED => __('enums.milestone_status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::IN_PROGRESS => 'primary',
            self::READY_FOR_REVIEW => 'warning',
            self::UNDER_REVIEW => 'warning',
            self::COMPLETED => 'success',
            self::OVERDUE => 'danger',
            self::BLOCKED => 'danger',
            self::CANCELLED => 'gray',
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

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }
}

