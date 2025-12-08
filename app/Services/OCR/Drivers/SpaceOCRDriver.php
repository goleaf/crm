<?php

declare(strict_types=1);

namespace App\Services\OCR\Drivers;

use App\Services\OCR\Contracts\DriverInterface;
use App\Services\OCR\DTOs\OCRResult;
use OcrSpace\OcrSpace;

final readonly class SpaceOCRDriver implements DriverInterface
{
    public function __construct(
        private OcrSpace $ocrSpace
    ) {}

    public function extract(string $filePath, array $options = []): OCRResult
    {
        $response = $this->ocrSpace->parseImage($filePath);

        $parsedText = $response->parsedText();

        return new OCRResult(
            text: $parsedText ?? '',
            rawResponse: $response->all(),
            isParsed: (bool) $parsedText,
            confidence: 0.0 // OCR.space simple response doesn't always provide per-document confidence easily in simple mode, defaulting to 0.0 or parsing deeper if needed.
        );
    }

    public function getName(): string
    {
        return 'space_ocr';
    }
}
