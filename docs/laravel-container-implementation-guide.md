# Laravel Container Service Pattern - Implementation Guide

## Quick Start

This guide shows you how to implement the Laravel container service pattern in your Filament v4.3+ application with practical, real-world examples.

## Step 1: Create a Service

### Example: Email Verification Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly int $timeout = 10
    ) {}
    
    public function verify(string $email): EmailVerificationResult
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->apiUrl}/verify", ['email' => $email]);
            
            if ($response->failed()) {
                Log::warning('Email verification API failed', [
                    'email' => $email,
                    'status' => $response->status(),
                ]);
                
                return new EmailVerificationResult(
                    isValid: false,
                    isDisposable: false,
                    error: 'API request failed'
                );
            }
            
            $data = $response->json();
            
            return new EmailVerificationResult(
                isValid: $data['valid'] ?? false,
                isDisposable: $data['disposable'] ?? false,
                error: null
            );
            
        } catch (\Exception $e) {
            Log::error('Email verification exception', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            
            return new EmailVerificationResult(
                isValid: false,
                isDisposable: false,
                error: $e->getMessage()
            );
        }
    }
}

// Result DTO
readonly class EmailVerificationResult
{
    public function __construct(
        public bool $isValid,
        public bool $isDisposable,
        public ?string $error = null
    ) {}
}
```

## Step 2: Register the Service

Add to `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    // Singleton - same instance throughout request
    $this->app->singleton(EmailVerificationService::class, function ($app) {
        return new EmailVerificationService(
            apiKey: config('services.email_verification.api_key'),
            apiUrl: config('services.email_verification.api_url'),
            timeout: config('services.email_verification.timeout', 10)
        );
    });
}
```

Add configuration to `config/services.php`:

```php
'email_verification' => [
    'api_key' => env('EMAIL_VERIFICATION_API_KEY'),
    'api_url' => env('EMAIL_VERIFICATION_API_URL', 'https://api.emailverification.com'),
    'timeout' => env('EMAIL_VERIFICATION_TIMEOUT', 10),
],
```

## Step 3: Use in Filament Resources

### In Form Fields

```php
use App\Services\EmailVerificationService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;

TextInput::make('email')
    ->email()
    ->required()
    ->suffixAction(
        Action::make('verify')
            ->icon('heroicon-o-check-badge')
            ->action(function ($state, $set, $get) {
                $verificationService = app(EmailVerificationService::class);
                $result = $verificationService->verify($state);
                
                if ($result->isValid && !$result->isDisposable) {
                    $set('email_verified', true);
                    
                    Notification::make()
                        ->title(__('app.notifications.email_verified'))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title(__('app.notifications.email_invalid'))
                        ->body($result->isDisposable 
                            ? __('app.notifications.disposable_email')
                            : __('app.notifications.invalid_email')
                        )
                        ->warning()
                        ->send();
                }
            })
    )
```

### In Table Actions

```php
use App\Services\Contact\ContactMergeService;
use Filament\Tables\Actions\Action;

Action::make('merge')
    ->label(__('app.actions.merge_contact'))
    ->icon('heroicon-o-arrows-right-left')
    ->form([
        Select::make('duplicate_id')
            ->label(__('app.labels.merge_with'))
            ->options(function ($record) {
                $duplicateService = app(ContactDuplicateDetectionService::class);
                return $duplicateService->findDuplicates($record)
                    ->pluck('name', 'id');
            })
            ->required()
            ->searchable(),
        
        CheckboxList::make('field_selections')
            ->label(__('app.labels.keep_fields_from'))
            ->options([
                'email' => __('app.labels.email'),
                'phone' => __('app.labels.phone'),
                'address' => __('app.labels.address'),
            ])
            ->descriptions([
                'email' => fn ($record) => $record->email,
                'phone' => fn ($record) => $record->phone,
            ]),
    ])
    ->action(function (People $record, array $data) {
        $mergeService = app(ContactMergeService::class);
        $duplicate = People::find($data['duplicate_id']);
        
        try {
            DB::transaction(function () use ($mergeService, $record, $duplicate, $data) {
                $mergeService->merge($record, $duplicate, $data['field_selections'] ?? []);
            });
            
            Notification::make()
                ->title(__('app.notifications.contacts_merged'))
                ->body(__('app.notifications.contacts_merged_body', [
                    'primary' => $record->name,
                    'duplicate' => $duplicate->name,
                ]))
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Contact merge failed', [
                'primary_id' => $record->id,
                'duplicate_id' => $duplicate->id,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title(__('app.notifications.merge_failed'))
                ->body(__('app.notifications.merge_failed_body'))
                ->danger()
                ->send();
        }
    })
    ->requiresConfirmation()
    ->modalHeading(__('app.modals.merge_contacts'))
    ->modalDescription(__('app.modals.merge_contacts_description'))
```

### In Resource Header Actions

```php
use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        Action::make('viewMetrics')
            ->label(__('app.actions.view_metrics'))
            ->icon('heroicon-o-chart-bar')
            ->color('primary')
            ->modalHeading(__('app.modals.opportunity_metrics'))
            ->modalDescription(__('app.modals.opportunity_metrics_description'))
            ->modalContent(function () {
                $metricsService = app(OpportunityMetricsService::class);
                $teamId = Filament::getTenant()->id;
                
                try {
                    $metrics = $metricsService->getTeamMetrics($teamId);
                    
                    return view('filament.modals.opportunity-metrics', [
                        'metrics' => $metrics,
                        'team' => Filament::getTenant(),
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to load metrics', [
                        'team_id' => $teamId,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return view('filament.modals.error', [
                        'message' => __('app.errors.metrics_unavailable'),
                    ]);
                }
            })
            ->modalWidth('5xl')
            ->slideOver(),
    ];
}
```

## Step 4: Create Service Tests

### Unit Test

```php
<?php

use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Http;

it('verifies valid email addresses', function () {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => true,
            'disposable' => false,
        ], 200),
    ]);
    
    $service = new EmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10
    );
    
    $result = $service->verify('john@example.com');
    
    expect($result->isValid)->toBeTrue();
    expect($result->isDisposable)->toBeFalse();
    expect($result->error)->toBeNull();
});

it('detects disposable email addresses', function () {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => true,
            'disposable' => true,
        ], 200),
    ]);
    
    $service = new EmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10
    );
    
    $result = $service->verify('temp@tempmail.com');
    
    expect($result->isValid)->toBeTrue();
    expect($result->isDisposable)->toBeTrue();
});

it('handles API failures gracefully', function () {
    Http::fake([
        'api.emailverification.com/*' => Http::response([], 500),
    ]);
    
    $service = new EmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10
    );
    
    $result = $service->verify('john@example.com');
    
    expect($result->isValid)->toBeFalse();
    expect($result->error)->toBe('API request failed');
});
```

### Feature Test

```php
<?php

use App\Models\People;
use App\Services\Contact\ContactMergeService;
use App\Services\Contact\ContactDuplicateDetectionService;

it('merges contacts and transfers all relationships', function () {
    $team = Team::factory()->create();
    
    $primary = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $duplicate = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jon Doe',
        'email' => 'jon@example.com',
    ]);
    
    // Create relationships for duplicate
    $task = Task::factory()->create(['people_id' => $duplicate->id]);
    $note = Note::factory()->create(['people_id' => $duplicate->id]);
    $opportunity = Opportunity::factory()->create(['people_id' => $duplicate->id]);
    
    $service = app(ContactMergeService::class);
    $result = $service->merge($primary, $duplicate, []);
    
    // Assert primary contact exists
    expect($result->id)->toBe($primary->id);
    
    // Assert relationships transferred
    expect($primary->fresh()->tasks)->toHaveCount(1);
    expect($primary->fresh()->notes)->toHaveCount(1);
    expect($primary->fresh()->opportunities)->toHaveCount(1);
    
    // Assert duplicate is soft deleted
    expect(People::withTrashed()->find($duplicate->id)->trashed())->toBeTrue();
    
    // Assert audit log created
    $this->assertDatabaseHas('contact_merge_log', [
        'primary_contact_id' => $primary->id,
        'duplicate_contact_id' => $duplicate->id,
    ]);
});
```

## Real-World Examples

### Example 1: Document Generation Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function __construct(
        private readonly string $storageDisk = 'invoices',
        private readonly string $viewTemplate = 'invoices.pdf'
    ) {}
    
    public function generate(Invoice $invoice): string
    {
        $pdf = Pdf::loadView($this->viewTemplate, [
            'invoice' => $invoice->load(['items', 'customer', 'company']),
        ]);
        
        $filename = "invoice-{$invoice->number}.pdf";
        $path = "invoices/{$invoice->team_id}/{$filename}";
        
        Storage::disk($this->storageDisk)->put($path, $pdf->output());
        
        $invoice->update(['pdf_path' => $path]);
        
        return $path;
    }
    
    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$invoice->pdf_path || !Storage::disk($this->storageDisk)->exists($invoice->pdf_path)) {
            $this->generate($invoice);
        }
        
        return Storage::disk($this->storageDisk)->download(
            $invoice->pdf_path,
            "invoice-{$invoice->number}.pdf"
        );
    }
}

// Register in AppServiceProvider
$this->app->singleton(InvoicePdfService::class, function () {
    return new InvoicePdfService(
        storageDisk: config('filesystems.invoices', 'local'),
        viewTemplate: config('invoices.pdf_template', 'invoices.pdf')
    );
});
```

### Example 2: Notification Service with Multiple Channels

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function __construct(
        private readonly bool $queueNotifications = true
    ) {}
    
    public function send(User $user, array $data): void
    {
        $channels = $this->determineChannels($user, $data);
        
        $notification = new \App\Notifications\GenericNotification(
            title: $data['title'],
            body: $data['body'],
            actionUrl: $data['action_url'] ?? null,
            actionLabel: $data['action_label'] ?? null
        );
        
        if ($this->queueNotifications) {
            $user->notify($notification->onQueue('notifications'));
        } else {
            $user->notify($notification);
        }
    }
    
    public function sendToTeam(\App\Models\Team $team, array $data): void
    {
        $team->users->each(function ($user) use ($data) {
            $this->send($user, $data);
        });
    }
    
    private function determineChannels(User $user, array $data): array
    {
        $channels = ['database']; // Always store in database
        
        if ($user->email_notifications_enabled && isset($data['email'])) {
            $channels[] = 'mail';
        }
        
        if ($user->sms_notifications_enabled && isset($data['sms'])) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }
}
```

### Example 3: Data Export Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class DataExportService
{
    public function __construct(
        private readonly string $storageDisk = 'exports',
        private readonly int $chunkSize = 1000
    ) {}
    
    public function exportToCsv(
        Collection $data,
        array $headers,
        string $filename
    ): string {
        $csv = Writer::createFromString();
        $csv->insertOne($headers);
        
        // Process in chunks to avoid memory issues
        $data->chunk($this->chunkSize)->each(function ($chunk) use ($csv) {
            $csv->insertAll($chunk->toArray());
        });
        
        $path = "exports/{$filename}";
        Storage::disk($this->storageDisk)->put($path, $csv->toString());
        
        return $path;
    }
    
    public function exportToJson(
        Collection $data,
        string $filename
    ): string {
        $path = "exports/{$filename}";
        
        Storage::disk($this->storageDisk)->put(
            $path,
            $data->toJson(JSON_PRETTY_PRINT)
        );
        
        return $path;
    }
    
    public function getDownloadUrl(string $path): string
    {
        return Storage::disk($this->storageDisk)->temporaryUrl(
            $path,
            now()->addHours(24)
        );
    }
}
```

## Advanced Patterns

### Pattern 1: Service with Repository

```php
<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;

class CompanyService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $repository,
        private readonly AuditLogService $auditLog
    ) {}
    
    public function create(array $data): Company
    {
        $company = $this->repository->create($data);
        
        $this->auditLog->log('company.created', [
            'company_id' => $company->id,
            'data' => $data,
        ]);
        
        return $company;
    }
    
    public function update(Company $company, array $data): Company
    {
        $original = $company->toArray();
        
        $updated = $this->repository->update($company, $data);
        
        $this->auditLog->log('company.updated', [
            'company_id' => $company->id,
            'original' => $original,
            'updated' => $data,
        ]);
        
        return $updated;
    }
}
```

### Pattern 2: Service with Events

```php
<?php

namespace App\Services;

use App\Events\LeadConverted;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Contracts\Events\Dispatcher;

class LeadConversionService
{
    public function __construct(
        private readonly Dispatcher $events
    ) {}
    
    public function convert(Lead $lead, array $opportunityData): Opportunity
    {
        $opportunity = DB::transaction(function () use ($lead, $opportunityData) {
            $opportunity = Opportunity::create([
                ...$opportunityData,
                'lead_id' => $lead->id,
                'team_id' => $lead->team_id,
            ]);
            
            $lead->update(['status' => 'converted']);
            
            return $opportunity;
        });
        
        $this->events->dispatch(new LeadConverted($lead, $opportunity));
        
        return $opportunity;
    }
}
```

### Pattern 3: Service with Caching Strategy

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ProductCatalogService
{
    public function __construct(
        private readonly int $cacheTtl = 3600,
        private readonly string $cachePrefix = 'products'
    ) {}
    
    public function getProducts(int $categoryId): Collection
    {
        return Cache::tags([$this->cachePrefix, "category.{$categoryId}"])
            ->remember(
                "{$this->cachePrefix}.category.{$categoryId}",
                $this->cacheTtl,
                fn () => Product::where('category_id', $categoryId)
                    ->with(['images', 'variants'])
                    ->get()
            );
    }
    
    public function clearCategoryCache(int $categoryId): void
    {
        Cache::tags([$this->cachePrefix, "category.{$categoryId}"])->flush();
    }
    
    public function clearAllCache(): void
    {
        Cache::tags([$this->cachePrefix])->flush();
    }
}
```

## Troubleshooting

### Issue: Service not resolving dependencies

**Problem**: Getting "Target class does not exist" error.

**Solution**: Make sure service is registered in `AppServiceProvider::register()`:

```php
$this->app->singleton(YourService::class);
```

### Issue: Circular dependency

**Problem**: Service A depends on Service B, which depends on Service A.

**Solution**: Refactor to extract shared logic into a third service, or use events to decouple.

### Issue: Service state persisting between requests

**Problem**: Service maintains state across multiple requests.

**Solution**: Use `bind()` instead of `singleton()`, or ensure service is stateless.

### Issue: Cannot mock service in tests

**Problem**: Service is instantiated directly instead of resolved from container.

**Solution**: Always use constructor injection or `app()` helper, never `new Service()`.

## Best Practices Checklist

- [ ] Service registered in `AppServiceProvider::register()`
- [ ] Constructor uses readonly properties
- [ ] Dependencies injected via constructor
- [ ] Service has single, clear responsibility
- [ ] Error handling with try-catch and logging
- [ ] Returns typed results (DTOs, models, arrays)
- [ ] Unit tests with mocked dependencies
- [ ] Feature tests with real dependencies
- [ ] Documentation in service docblock
- [ ] Configuration values from config files

## References

- Full documentation: `docs/laravel-container-services.md`
- Steering guide: `.kiro/steering/laravel-container-services.md`
- Laravel container docs: https://laravel.com/docs/container
- Filament actions: https://filamentphp.com/docs/actions
