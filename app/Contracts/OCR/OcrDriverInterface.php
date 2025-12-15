<?php

declare(strict_types=1);

namespace App\Contracts\OCR;

/**
 * OCR driver interface for multiple OCR engines.
 *
 * Implementations: TesseractDriver, GoogleVisionDriver, AwsTextractDriver
 */
interface OcrDriverInterface
{
    /**
     * Extract text from an image or PDF file.
     *
     * @return array{text: string, confidence: float, metadata: array<string, mixed>}
     */
    public function extractText(string $filePath): array;

    /**
     * Check if the driver is available and configured.
     */
    public function isAvailable(): bool;

    /**
     * Get the driver name.
     */
    public function getName(): string;
}
