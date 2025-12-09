# Facade Maker Integration

## Overview
`vivek-mistry/facade-maker` scaffolds facades and their backing service classes so new facades follow a consistent structure.

## Installation
- Added as a dev dependency: `composer require --dev vivek-mistry/facade-maker`.
- Upstream stub paths are case-sensitive on Linux; a composer patch (`patches/vivek-mistry-facade-maker-stub-case.patch`) fixes the path so the command can find its stubs on all filesystems.

## Usage
- Run `php artisan app:facade-maker {FacadeName?} {FacadeServiceClass?}`; omit arguments to answer the interactive prompts.
- Generates `app/Facades/{FacadeName}.php` and `app/Facades/Services/{FacadeServiceClass}.php` using the provided stubs.
- Register the service in `AppServiceProvider` so the facade resolves correctly:

```php
use App\Facades\Services\CommonFileUpload;

$this->app->singleton('commonfileupload', static fn () => app(CommonFileUpload::class));
```

## Conventions
- Keep generated service classes typed and follow the container patterns in `docs/laravel-container-services.md`.
- Keep the accessor string (the first argument to `singleton`) lowercase, matching the package’s generation logic.
- If you update the package, keep the patch entry in `composer.json` so stub resolution stays portable.
