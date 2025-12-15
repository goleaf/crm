<?php

declare(strict_types=1);

namespace App\Enums\Knowledge;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CommentStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case HIDDEN = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.comment_status.pending'),
            self::APPROVED => __('enums.comment_status.approved'),
            self::HIDDEN => __('enums.comment_status.hidden'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::HIDDEN => 'gray',
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
