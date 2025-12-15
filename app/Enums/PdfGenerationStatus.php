<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PdfGenerationStatus: string implements HasLabel
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.pdf_generation_status.pending'),
            self::PROCESSING => __('enums.pdf_generation_status.processing'),
            self::COMPLETED => __('enums.pdf_generation_status.completed'),
            self::FAILED => __('enums.pdf_generation_status.failed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
