<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcessApprovalStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ESCALATED = 'escalated';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.process_approval_status.pending'),
            self::APPROVED => __('enums.process_approval_status.approved'),
            self::REJECTED => __('enums.process_approval_status.rejected'),
            self::ESCALATED => __('enums.process_approval_status.escalated'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::ESCALATED => 'danger',
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
