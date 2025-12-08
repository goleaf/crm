<?php

use App\Services\Config\ConfigCheckerService;
use Illuminate\Support\Facades\Log;

return [
    'name' => 'Config Checker Auto-Run',
    'event' => 'file_saved',
    'pattern' => '{config/**/*.php,.env}',
    'action' => function ($file) {
        try {
            // When config files change, run the check and cache it.
            // We use the service to keep things consistent.
            $service = app(ConfigCheckerService::class);
            $service->clearCache();
            $result = $service->check();

            if ($result['status'] === 'issues_found') {
                Log::warning("Config Checker found issues after {$file} changed.", ['issues' => $result['issues']]);
            } else {
                Log::info("Config Checker passed after {$file} changed.");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to run Config Checker hook: " . $e->getMessage());
        }
    },
];
