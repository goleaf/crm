<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OCRDocumentStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.ocr_document_status.pending'),
            self::PROCESSING => __('enums.ocr_document_status.processing'),
            self::COMPLETED => __('enums.ocr_document_status.completed'),
            self::FAILED => __('enums.ocr_document_status.failed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
