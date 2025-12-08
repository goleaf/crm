<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OCR Driver
    |--------------------------------------------------------------------------
    |
    | The OCR driver to use for text extraction. Supported: "tesseract"
    | Additional drivers can be implemented: "google_vision", "aws_textract"
    |
    */
    'driver' => env('OCR_DRIVER', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | Tesseract Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Tesseract OCR engine.
    |
    */
    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
        'language' => env('TESSERACT_LANGUAGE', 'eng'),
        'timeout' => (int) env('TESSERACT_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Cleanup
    |--------------------------------------------------------------------------
    |
    | Enable AI-powered text cleanup using Prism PHP.
    |
    */
    'ai_enabled' => (bool) env('OCR_AI_ENABLED', false),
    'ai_model' => env('OCR_AI_MODEL', 'openai:gpt-4o-mini'),
    'ai_temperature' => (float) env('OCR_AI_TEMPERATURE', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum confidence score (0-1) for accepting OCR results.
    | Results below this threshold will be flagged for review.
    |
    */
    'confidence_threshold' => (float) env('OCR_CONFIDENCE_THRESHOLD', 0.7),

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    |
    | File upload validation rules.
    |
    */
    'max_file_size' => (int) env('OCR_MAX_FILE_SIZE', 10240), // KB
    'allowed_mime_types' => [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/tiff',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for OCR processing jobs.
    |
    */
    'queue' => [
        'name' => env('OCR_QUEUE', 'ocr-processing'),
        'timeout' => (int) env('OCR_QUEUE_TIMEOUT', 300),
        'tries' => (int) env('OCR_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Storage paths for OCR files and results.
    |
    */
    'storage' => [
        'uploads_path' => 'ocr/uploads',
        'results_path' => 'ocr/results',
        'disk' => env('OCR_STORAGE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Cache
    |--------------------------------------------------------------------------
    |
    | Cache TTL for OCR templates (in seconds).
    |
    */
    'template_cache_ttl' => (int) env('OCR_TEMPLATE_CACHE_TTL', 3600),
];
