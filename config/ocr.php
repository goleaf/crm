<?php

declare(strict_types=1);

return [
    'default' => env('OCR_DRIVER', 'space_ocr'),

    'drivers' => [
        'space_ocr' => [
            'key' => env('OCR_SPACE_KEY'),
            'endpoint' => 'https://api.ocr.space/parse/image',
        ],
        'tesseract' => [
            'path' => env('TESSERACT_PATH', '/usr/local/bin/tesseract'),
        ],
    ],

    'ai' => [
        'enabled' => env('OCR_AI_ENABLED', false),
        'model' => env('OCR_AI_MODEL', 'claude-3-haiku'),
    ],

    'upload' => [
        'max_size_kb' => 10240, // 10MB
        'accepted_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
    ],
];
