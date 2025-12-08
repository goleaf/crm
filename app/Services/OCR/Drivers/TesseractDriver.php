<?php

declare(strict_types=1);

namespace App\Services\OCR\Drivers;

use App\Services\OCR\Contracts\DriverInterface;
use App\Services\OCR\DTOs\OCRResult;
use App\Services\OCR\Exceptions\OCRException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final readonly class TesseractDriver implements DriverInterface
{
    public function __construct(
        private string $tesseractPath,
        private string $language,
        private int $psm,
        private int $oem,
        private int $timeout,
    ) {}

    public function process(string $filePath): OCRResult
    {
        $startTime = microtime(true);

        try {
            if (! File::exists($filePath)) {
                throw new OCRException("File not found: {$filePath}");
            }

            $outputPath = $this->getTempOutputPath();
            $text = $this->executeTesseract($filePath, $outputPath);
            $confidence = $this->calculateConfidence($text);

            $processingTime = microtime(true) - $startTime;

            Log::info('OCR processing completed', [
                'driver' => 'tesseract',
                'file' => basename($filePath),
                'confidence' => $confidence,
                'processing_time' => $processingTime,
            ]);

            return new OCRResult(
                text: $text,
                confidence: $confidence,
                processingTime: $processingTime,
                metadata: [
                    'driver' => 'tesseract',
                    'language' => $this->language,
                    'psm' => $this->psm,
                    'oem' => $this->oem,
                ],
            );
        } catch (\Throwable $e) {
            $processingTime = microtime(true) - $startTime;

            Log::error('OCR processing failed', [
                'driver' => 'tesseract',
                'file' => basename($filePath),
                'error' => $e->getMessage(),
                'processing_time' => $processingTime,
            ]);

            return new OCRResult(
                text: '',
                confidence: 0.0,
                processingTime: $processingTime,
                error: $e->getMessage(),
            );
        }
    }

    public function isAvailable(): bool
    {
        try {
            $process = new Process([$this->tesseractPath, '--version']);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'tesseract';
    }

    private function executeTesseract(string $inputPath, string $outputPath): string
    {
        $command = [
            $this->tesseractPath,
            $inputPath,
            $outputPath,
            '-l', $this->language,
            '--psm', (string) $this->psm,
            '--oem', (string) $this->oem,
        ];

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new OCRException("Tesseract execution failed: {$e->getMessage()}", 0, $e);
        }

        $textFile = $outputPath.'.txt';

        if (! File::exists($textFile)) {
            throw new OCRException('Tesseract did not produce output file');
        }

        $text = File::get($textFile);
        File::delete($textFile);

        return trim($text);
    }

    private function getTempOutputPath(): string
    {
        $tempDir = config('ocr.paths.temp');

        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        return $tempDir.'/'.uniqid('ocr_', true);
    }

    private function calculateConfidence(string $text): float
    {
        if ($text === '' || $text === '0') {
            return 0.0;
        }

        // Simple heuristic: calculate confidence based on text characteristics
        $totalChars = strlen($text);
        $alphanumericChars = strlen((string) preg_replace('/[^a-zA-Z0-9]/', '', $text));
        $wordCount = str_word_count($text);

        if ($totalChars === 0) {
            return 0.0;
        }

        // Higher confidence if more alphanumeric characters and reasonable word count
        $alphanumericRatio = $alphanumericChars / $totalChars;
        $avgWordLength = $wordCount > 0 ? $alphanumericChars / $wordCount : 0;

        // Confidence score between 0 and 1
        $confidence = min(1.0, ($alphanumericRatio * 0.7) + (min($avgWordLength / 10, 0.3)));

        return round($confidence, 2);
    }
}
