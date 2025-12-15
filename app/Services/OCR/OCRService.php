<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Services\OCR\Contracts\DriverInterface;
use App\Services\OCR\DTOs\OCRResult;
use App\Services\OCR\Processors\TextCleaner;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class OCRService
{
    public function __construct(
        private DriverInterface $driver,
        private TextCleaner $textCleaner,
    ) {}

    /**
     * Process a file and return extracted text.
     *
     * @throws Exception
     */
    public function process(string $filePath, array $options = []): OCRResult
    {
        Log::info("Starting OCR processing for file: {$filePath}");

        try {
            $result = $this->driver->extract($filePath, $options);

            // Clean text if it was extracted
            if ($result->isParsed && ($result->text !== '' && $result->text !== '0')) {
                $cleanedText = $this->textCleaner->clean($result->text);

                // Return new result with cleaned text
                // Note: We're recreating DTO because properties are readonly
                return new OCRResult(
                    text: $cleanedText,
                    rawResponse: $result->rawResponse,
                    isParsed: true,
                    confidence: $result->confidence,
                );
            }

            return $result;

        } catch (Exception $e) {
            Log::error('OCR Processing failed: ' . $e->getMessage());

            throw $e;
        }
    }

    public function getDriverName(): string
    {
        return $this->driver->getName();
    }
}
