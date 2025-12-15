<?php

declare(strict_types=1);

namespace App\Enums\Knowledge;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArticleVisibility: string implements HasColor, HasLabel
{
    case PUBLIC = 'public';
    case INTERNAL = 'internal';
    case RESTRICTED = 'restricted';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => __('enums.article_visibility.public'),
            self::INTERNAL => __('enums.article_visibility.internal'),
            self::RESTRICTED => __('enums.article_visibility.restricted'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PUBLIC => 'success',
            self::INTERNAL => 'info',
            self::RESTRICTED => 'warning',
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
