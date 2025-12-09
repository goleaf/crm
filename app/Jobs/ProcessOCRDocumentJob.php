<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OCRDocument;
use App\Services\OCR\Exceptions\OCRException;
use App\Services\OCR\OCRService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ProcessOCRDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;

    public int $tries;

    public array $backoff;

    public function __construct(
        public readonly int $documentId,
    ) {
        $this->timeout = config('ocr.queue.timeout', 300);
        $this->tries = config('ocr.queue.tries', 3);
        $this->backoff = config('ocr.queue.backoff', [60, 180, 600]);
        $this->onQueue(config('ocr.queue.queue', 'ocr-processing'));
    }

    public function handle(OCRService $ocrService): void
    {
        $document = OCRDocument::find($this->documentId);

        if ($document === null) {
            Log::warning('OCR document not found', ['document_id' => $this->documentId]);

            return;
        }

        try {
            $document->markAsProcessing();

            $startTime = microtime(true);

            // Process document with template if available
            if ($document->template_id) {
                $extractedData = $ocrService->processWithTemplate(
                    Storage::path($document->file_path),
                    $document->template_id,
                );

                $document->update([
                    'status' => 'completed',
                    'extracted_data' => $extractedData->fields,
                    'raw_text' => $extractedData->rawText,
                    'confidence_score' => $extractedData->confidence,
                    'processing_time' => microtime(true) - $startTime,
                    'validation_errors' => $extractedData->validationErrors,
                    'processed_at' => now(),
                ]);

                // Increment template usage
                $document->template->incrementUsage();
            } else {
                // Extract raw text only
                $result = $ocrService->extractText(Storage::path($document->file_path));

                if (! $result->isSuccessful()) {
                    throw new OCRException($result->error ?? 'OCR processing failed');
                }

                $document->update([
                    'status' => 'completed',
                    'raw_text' => $result->text,
                    'confidence_score' => $result->confidence,
                    'processing_time' => $result->processingTime,
                    'processed_at' => now(),
                ]);
            }

            Log::info('OCR document processed successfully', [
                'document_id' => $this->documentId,
                'confidence' => $document->confidence_score,
                'processing_time' => $document->processing_time,
            ]);
        } catch (\Throwable $e) {
            $document->markAsFailed($e->getMessage());

            Log::error('OCR document processing failed', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if we should alert on failures
            if (config('ocr.monitoring.alert_on_failures')) {
                $this->checkFailureThreshold();
            }

            throw $e;
        }
    }

    private function checkFailureThreshold(): void
    {
        $threshold = config('ocr.monitoring.failure_threshold', 5);
        $recentFailures = OCRDocument::failed()
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures >= $threshold) {
            Log::critical('OCR failure threshold exceeded', [
                'failures' => $recentFailures,
                'threshold' => $threshold,
                'period' => '1 hour',
            ]);

            // TODO: Send notification to admins
        }
    }

    public function failed(\Throwable $exception): void
    {
        $document = OCRDocument::find($this->documentId);

        if ($document) {
            $document->markAsFailed($exception->getMessage());
        }

        Log::error('OCR job failed permanently', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
