# Laravel Onym Integration

## Overview
We generate structured, secure filenames using the `blaspsoft/onym` package. This ensures uploads have predictable, collision-free names (e.g., UUIDs, timestamps) rather than relying on raw user input.

## Features
- **Strategies**: UUID (default), Timestamp, Hash, Slug, Random.
- **Conventions**: All user uploads must use `Onym` to generate filenames.
- **Integration**: Seamlessly works with Filament `FileUpload` fields.

## Configuration
Defaults are set in `config/onym.php`.
- **Default Strategy**: `uuid` (best for general storage).
- **Default Extension**: `txt` (overridden by file MIME type usually).

## Usage

### Filament Integration
In your Filament resources, use `getUploadedFileNameForStorageUsing` to sanitize filenames:

```php
use Blaspsoft\Onym\Facades\Onym;
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->getUploadedFileNameForStorageUsing(
        fn ($file) => Onym::make(
            defaultFilename: '',
            extension: $file->getClientOriginalExtension(),
            strategy: 'uuid',
            options: ['suffix' => '_doc']
        )
    );
```

### Manual Usage
```php
use Blaspsoft\Onym\Facades\Onym;

// Generate UUID filename
$name = Onym::uuid('pdf'); // "123e4567-e89b-12d3... .pdf"

// Generate SEO-friendly slug
$name = Onym::slug('My Report', 'pdf'); // "my-report.pdf"
```

## Rules
- **Always** sanitize user uploads.
- **Prefer UUID** for private/internal docs to avoid guessing.
- **Prefer Slug** for public downloads if SEO is needed.
