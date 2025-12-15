<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PdfTemplateStatus: string implements HasLabel
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('enums.pdf_template_status.draft'),
            self::ACTIVE => __('enums.pdf_template_status.active'),
            self::ARCHIVED => __('enums.pdf_template_status.archived'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
