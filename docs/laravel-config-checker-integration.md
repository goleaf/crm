# Laravel Config Checker Integration Guide

## Overview
The [laravel-config-checker](https://github.com/chrisdicarlo/laravel-config-checker) package scans your application for missing configuration keys. We have integrated it into Filament to provide a visual health check of your configuration files.

## Features
- **CLI Check**: Run `php artisan config:check` to scan via terminal.
- **Filament Page**: View detailed reports at `System > Config Checker`.
- **Dashboard Widget**: Monitor config health status directly from the dashboard.
- **Service Integration**: `ConfigCheckerService` allows programmatic access to check results.

## Configuration
The integration uses a custom service wrapper `App\Services\Config\ConfigCheckerService`.

### Caching
The check results are cached for 5 minutes (300 seconds) by default to prevent expensive scanning on every page load. You can manually refresh the check via the "Run Check" button in Filament.

## Usage

### Via Artisan
```bash
php artisan config:check
```

### Via Filament
Navigate to **System > Config Checker** in the admin panel.
- **Healthy**: Shows a green success message.
- **Issues**: Displays a table of missing keys, file locations, and usage methods.
- **Run Check**: Click the button in the header to force a re-scan.

### Dashboard
A widget on the dashboard shows a quick status summary (Healthy/Issues Found) and links to the full report.

### Programmatic Usage
```php
use App\Services\Config\ConfigCheckerService;

$service = app(ConfigCheckerService::class);

// Run a fresh check (slow)
$results = $service->check(); 
// Returns: ['status' => 'healthy'|'issues_found', 'issues' => [...], 'raw_output' => '...']

// Get cached results (fast)
$cached = $service->getCachedCheck(); 

// Clear cache
$service->clearCache();
```

## Troubleshooting
- **Missing Keys**: If the checker reports a missing key, check your `.env` file or `config/*.php` files.
- **False Positives**: The checker scans code for `config('key')` usage. If you dynamically generate keys, you might get false positives.
- **Parsing Issues**: If the Filament page shows empty results but the CLI shows errors, the output format of the package might have changed. The service uses regex/string parsing on the CLI output.

## Updates
When updating `laravel-config-checker`, verify that the CLI output format has not changed significantly, as the Service relies on parsing the text table.
