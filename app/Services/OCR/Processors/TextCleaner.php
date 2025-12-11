<?php

declare(strict_types=1);

namespace App\Services\OCR\Processors;

final class TextCleaner
{
    public function clean(string $text): string
    {
        // Simple passthrough - AI cleanup has been removed
        return $text;
    }
}
