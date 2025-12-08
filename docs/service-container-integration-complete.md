# Complete Service Container Integration Guide

## Overview

This document provides a comprehensive overview of the service container integration throughout the Relaticle CRM application, demonstrating real-world implementations following all established patterns from the steering files.

## Architecture Summary

### Service Types

1. **Action Services** (Transient - `bind()`)
   - Fresh instance per resolution
   - Stateless business operations
   - Examples: `ContactMergeService`, `ExampleActionService`

2. **Query Services** (Singleton - `singleton()`)
   - Shared instance with caching
   - Data fetching and aggregation
   - Examples: `OpportunityMetricsService`, `ExampleQueryService`

3. **Integration Services** (Singleton - `singleton()`)
   - External API communication
   - Shared HTTP client configuration
   - Examples: `GitHubService`, `ExampleIntegrationService`

4. **Repository Services** (Singleton - `singleton()`)
   - Data access abstraction
   - Interface-based implementations
   - Examples: `EloquentPeopleRepository`, `EloquentCompanyRepository`

5. **Utility Services** (Transient - `bind()`)
   - Stateless helper operations
   - Examples: `VCardService`, `OcrCleanupService`

## Complete Implementation Examples

### 1. OCR Service Architecture

The OCR integration demonstrates all service container patterns:

```php
// Driver Interface
interface OcrDriverInterface
{
    public function extractText(string $filePath): array;
    public function isAvailable(): bool;
    public function getName(): string;
}

// Concrete Driver
final readonly class TesseractDriver implements OcrDriverInterface
{
    public function __construct(
        private string $tesseractPath = 'tesseract',
        private string $language = 'eng',
        private int $timeout = 300
    ) {}
}

// Main Service
final readonly class OcrService
{
    public function __construct(
        private OcrDriverInterface $driver,
        private OcrTemplateService $templateService,
        private OcrCleanupService $cleanupService,
        private float $confidenceThreshold = 0.7
    ) {}
}

// Registration in AppServiceProvider
private function registerOcrServices(): void
{
    // Driver binding
    $this->app->singleton(OcrDriverInterface::class, function ($app) {
        return new TesseractDriver(
            tesseractPath: config('ocr.tesseract.path'),
            language: config('ocr.tesseract.language'),
            timeout: config('ocr.tesseract.timeout')
        );
    });
    
    // Service bindings
    $this->app->singleton(OcrTemplateService::class);
    $this->app->singleton(OcrCleanupService::class);
    $this->app->singleton(OcrService::class);
}
```

### 2. Filament Integration Patterns

#### Resource Actions

```php
// In OcrDocumentResource
Tables\Actions\Action::make('reprocess')
    ->action(function (OcrDocument $record, OcrService $service): void {
        ProcessOcrDocument::dispatch($record);
        
        Notification::make()
            ->title(__('ocr.notifications.reprocessing_started'))
            ->success()
            ->send();
    })
    ->requiresConfirmation();
```

#### Widget Integration

```php
final class OpportunityStatsWidget extends BaseWidget
{
    public function __construct(
        private readonly OpportunityMetricsService $metricsService
    ) {
        parent::__construct();
    }
    
    protected function getStats(): array
    {
        $metrics = $this->metricsService->getTeamMetrics(
            Filament::getTenant()->id
        );
        
        return [/* stats */];
    }
}
```

### 3. Queue Integration

```php
final class ProcessOcrDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 300;
    
    public function backoff(): array
    {
        return [30, 60, 120]; // Exponential backoff
    }
    
    public function handle(OcrService $ocrService): void
    {
        $result = $ocrService->processDocument($this->document);
        
        if (!$result['success']) {
            throw new \RuntimeException($result['error']);
        }
    }
}
```

### 4. Testing Patterns

#### Unit Tests with Mocks

```php
it('processes document with mocked dependencies', function () {
    $driver = Mockery::mock(OcrDriverInterface::class);
    $templateService = Mockery::mock(OcrTemplateService::class);
    $cleanupService = Mockery::mock(OcrCleanupService::class);
    
    $driver->shouldReceive('extractText')
        ->once()
        ->andReturn([
            'text' => 'Sample text',
            'confidence' => 0.85,
            'metadata' => [],
        ]);
    
    $service = new OcrService($driver, $templateService, $cleanupService);
    $result = $service->processDocument($document);
    
    expect($result['success'])->toBeTrue();
});
```

#### Feature Tests with Real Services

```php
it('processes document end-to-end', function () {
    $document = OcrDocument::factory()->create([
        'file_path' => 'test-document.pdf',
        'status' => 'pending',
    ]);
    
    $service = app(OcrService::class);
    $result = $service->processDocument($document);
    
    expect($result['success'])->toBeTrue();
    expect($document->fresh()->status)->toBe('completed');
});
```

## Service Registration Checklist

When adding a new service:

1. ✅ Create service class in `app/Services/{Domain}/`
2. ✅ Use constructor injection with readonly properties
3. ✅ Register in `AppServiceProvider::register()`
4. ✅ Choose appropriate binding type:
   - `bind()` for stateless services
   - `singleton()` for services with caching/state
5. ✅ Create interface if swappable implementations needed
6. ✅ Write unit tests with mocked dependencies
7. ✅ Write feature tests with real dependencies
8. ✅ Document public methods and return types
9. ✅ Handle errors gracefully with try-catch
10. ✅ Log errors with context

## Configuration Pattern

All services use configuration-based initialization:

```php
// config/ocr.php
return [
    'driver' => env('OCR_DRIVER', 'tesseract'),
    'confidence_threshold' => (float) env('OCR_CONFIDENCE_THRESHOLD', 0.7),
    'ai_enabled' => (bool) env('OCR_AI_ENABLED', false),
];

// Service registration
$this->app->singleton(OcrService::class, function ($app) {
    return new OcrService(
        driver: $app->make(OcrDriverInterface::class),
        templateService: $app->make(OcrTemplateService::class),
        cleanupService: $app->make(OcrCleanupService::class),
        confidenceThreshold: config('ocr.confidence_threshold')
    );
});
```

## Performance Considerations

### Caching Strategy

```php
final class OcrTemplateService
{
    public function getTemplate(int $templateId): ?OcrTemplate
    {
        return Cache::remember(
            "ocr.template.{$templateId}",
            $this->cacheTtl,
            fn () => OcrTemplate::find($templateId)
        );
    }
    
    public function clearCache(int $templateId): void
    {
        Cache::forget("ocr.template.{$templateId}");
    }
}
```

### Queue Processing

```php
// Dispatch to dedicated queue
ProcessOcrDocument::dispatch($document)
    ->onQueue('ocr-processing');

// Configure in config/queue.php
'connections' => [
    'redis' => [
        'queues' => [
            'default',
            'ocr-processing',
        ],
    ],
],
```

## Error Handling Pattern

```php
public function processDocument(OcrDocument $document): array
{
    try {
        $result = $this->driver->extractText($document->file_path);
        
        // Process result...
        
        return ['success' => true, 'document' => $document];
    } catch (\Exception $e) {
        Log::error('OCR processing failed', [
            'document_id' => $document->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        $document->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

## Integration with Existing Patterns

### Works With

- **HTTP Client Macros**: Use `Http::external()` in integration services
- **Array Helpers**: Use `ArrayHelper` for data formatting
- **Date Scopes**: Services leverage model date scopes
- **Filament Actions**: Services power resource actions
- **Queue Jobs**: Services called from queued jobs
- **Precognition**: Services validate form data
- **Translations**: All UI text uses translation keys
- **Security Headers**: Services respect security middleware
- **Rector**: Services follow modern PHP patterns

## Directory Structure

```
app/
├── Contracts/
│   ├── OCR/
│   │   └── OcrDriverInterface.php
│   └── Repositories/
│       └── ExampleRepositoryInterface.php
├── Services/
│   ├── Example/
│   │   ├── ExampleActionService.php
│   │   ├── ExampleIntegrationService.php
│   │   └── ExampleQueryService.php
│   ├── OCR/
│   │   ├── Drivers/
│   │   │   └── TesseractDriver.php
│   │   ├── OcrService.php
│   │   ├── OcrTemplateService.php
│   │   └── OcrCleanupService.php
│   └── Contacts/
│       ├── ContactMergeService.php
│       ├── ContactDuplicateDetectionService.php
│       └── VCardService.php
├── Repositories/
│   └── EloquentExampleRepository.php
├── Jobs/
│   └── ProcessOcrDocument.php
└── Filament/
    ├── Resources/
    │   └── OcrDocumentResource.php
    └── Widgets/
        └── ExampleServiceWidget.php
```

## Related Documentation

- [Laravel Service Container Integration](./laravel-service-container-integration.md) - Comprehensive guide
- [Service Container Examples](./service-container-examples.md) - Practical examples
- [Laravel Container Services](./laravel-container-services.md) - Original reference
- [OCR Integration](./ocr-integration-strategy.md) - OCR-specific patterns
- [Filament v4 Conventions](../.kiro/steering/filament-conventions.md) - Filament patterns
- [Testing Standards](../.kiro/steering/testing-standards.md) - Testing requirements
- [Laravel Conventions](../.kiro/steering/laravel-conventions.md) - Laravel patterns

## Summary

The service container integration is now complete with:

✅ Example services demonstrating all patterns
✅ OCR services with driver pattern
✅ Filament resource integration
✅ Queue job integration
✅ Comprehensive testing examples
✅ Complete documentation
✅ AppServiceProvider registration
✅ Configuration management
✅ Error handling patterns
✅ Performance optimization
✅ Integration with existing patterns

All services follow the established patterns from steering files and are ready for production use.
