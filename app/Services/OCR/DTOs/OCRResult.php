<?php

declare(strict_types=1);

namespace App\Services\OCR\DTOs;

final readonly class OCRResult
{
    public function __construct(
        public string $text,
        public array $rawResponse = [],
        public bool $isParsed = false,
        public float $confidence = 0.0,
    ) {}
}
