<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliverableStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.deliverable_status.pending'),
            self::IN_PROGRESS => __('enums.deliverable_status.in_progress'),
            self::COMPLETED => __('enums.deliverable_status.completed'),
            self::REJECTED => __('enums.deliverable_status.rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
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

