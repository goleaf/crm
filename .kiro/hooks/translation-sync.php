<?php

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Support\Facades\Log;

return [
    'name' => 'Translation Sync',
    'event' => 'file_saved',
    'pattern' => 'lang/en/**/*.php',
    'action' => function ($file) {
        try {
            $service = app(TranslationCheckerService::class);
            $service->importFromFiles();
            Log::info("Auto-imported translations from {$file}");
        } catch (\Throwable $e) {
            Log::error("Failed to auto-import translations: " . $e->getMessage());
        }
    },
];
