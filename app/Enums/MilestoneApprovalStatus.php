<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MilestoneApprovalStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case INFO_REQUESTED = 'info_requested';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.milestone_approval_status.pending'),
            self::APPROVED => __('enums.milestone_approval_status.approved'),
            self::REJECTED => __('enums.milestone_approval_status.rejected'),
            self::INFO_REQUESTED => __('enums.milestone_approval_status.info_requested'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::INFO_REQUESTED => 'primary',
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

