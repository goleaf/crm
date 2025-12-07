<?php

declare(strict_types=1);

namespace App\Enums\Knowledge;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArticleStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('enums.article_status.draft'),
            self::PENDING_REVIEW => __('enums.article_status.pending_review'),
            self::PUBLISHED => __('enums.article_status.published'),
            self::ARCHIVED => __('enums.article_status.archived'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'warning',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'danger',
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
