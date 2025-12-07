<?php

declare(strict_types=1);

namespace App\Enums\Knowledge;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FaqStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('enums.faq_status.draft'),
            self::PUBLISHED => __('enums.faq_status.published'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
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
