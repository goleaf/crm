# Laravel Container Service Pattern

## Overview

The Laravel container service pattern leverages dependency injection and the service container to create loosely coupled, testable, and maintainable services. This pattern is essential for building scalable Filament v4.3+ applications with clean architecture.

## Core Concepts

### What is the Service Container?

Laravel's service container is a powerful tool for managing class dependencies and performing dependency injection. It automatically resolves dependencies when instantiating classes, making your code more flexible and testable.

### Benefits of Container-Based Services

1. **Automatic Dependency Resolution**: The container automatically injects dependencies
2. **Testability**: Easy to mock dependencies in tests
3. **Flexibility**: Swap implementations without changing consuming code
4. **Single Responsibility**: Services focus on specific business logic
5. **Reusability**: Services can be used across controllers, commands, jobs, and Filament resources

## Service Architecture Patterns

### 1. Basic Service Registration

Register services in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Singleton - same instance throughout request
    $this->app->singleton(GitHubService::class, function ($app) {
        return new GitHubService(
            config('services.github.token')
        );
    });
    
    // Bind - new instance each time
    $this->app->bind(InvoiceService::class, function ($app) {
        return new InvoiceService(
            $app->make(PdfService::class),
            $app->make(NotificationService::class)
        );
    });
}
```

### 2. Interface-Based Services

Use interfaces for flexibility and testing:

```php
// app/Contracts/Services/PaymentProcessorInterface.php
namespace App\Contracts\Services;

interface PaymentProcessorInterface
{
    public function charge(float $amount, string $token): PaymentResult;
    public function refund(string $transactionId): RefundResult;
}

// app/Services/StripePaymentProcessor.php
namespace App\Services;

use App\Contracts\Services\PaymentProcessorInterface;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly LoggerInterface $logger
    ) {}
    
    public function charge(float $amount, string $token): PaymentResult
    {
        // Stripe-specific implementation
    }
}

// Register in AppServiceProvider
$this->app->bind(
    PaymentProcessorInterface::class,
    StripePaymentProcessor::class
);
```

### 3. Repository Pattern with Container

```php
// app/Contracts/Repositories/CompanyRepositoryInterface.php
namespace App\Contracts\Repositories;

interface CompanyRepositoryInterface
{
    public function find(int $id): ?Company;
    public function findByTeam(int $teamId): Collection;
    public function create(array $data): Company;
}

// app/Repositories/EloquentCompanyRepository.php
namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;

class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly Company $model
    ) {}
    
    public function find(int $id): ?Company
    {
        return $this->model->find($id);
    }
    
    public function findByTeam(int $teamId): Collection
    {
        return $this->model->where('team_id', $teamId)->get();
    }
    
    public function create(array $data): Company
    {
        return $this->model->create($data);
    }
}

// Register in AppServiceProvider
$this->app->bind(
    CompanyRepositoryInterface::class,
    EloquentCompanyRepository::class
);
```

## Service Patterns for Filament v4.3+

### 1. Action Services

Services that perform specific business actions:

```php
// app/Services/Contact/ContactMergeService.php
namespace App\Services\Contact;

use App\Models\People;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactMergeService
{
    public function __construct(
        private readonly ContactDuplicateDetectionService $duplicateDetection,
        private readonly AuditLogService $auditLog
    ) {}
    
    public function merge(People $primary, People $duplicate, array $fieldSelections): People
    {
        return DB::transaction(function () use ($primary, $duplicate, $fieldSelections) {
            // Transfer relationships
            $this->transferRelationships($duplicate, $primary);
            
            // Merge field data
            $this->mergeFields($primary, $duplicate, $fieldSelections);
            
            // Archive duplicate
            $duplicate->delete();
            
            // Create audit log
            $this->auditLog->logMerge($primary, $duplicate);
            
            return $primary->fresh();
        });
    }
    
    private function transferRelationships(People $from, People $to): void
    {
        // Transfer tasks
        $from->tasks()->update(['people_id' => $to->id]);
        
        // Transfer notes
        $from->notes()->update(['people_id' => $to->id]);
        
        // Transfer opportunities
        $from->opportunities()->update(['people_id' => $to->id]);
    }
    
    private function mergeFields(People $primary, People $duplicate, array $fieldSelections): void
    {
        foreach ($fieldSelections as $field => $source) {
            if ($source === 'duplicate' && $duplicate->$field !== null) {
                $primary->$field = $duplicate->$field;
            }
        }
        
        $primary->save();
    }
}
```

### 2. Query Services

Services that handle complex queries:

```php
// app/Services/Opportunities/OpportunityMetricsService.php
namespace App\Services\Opportunities;

use App\Models\Opportunity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OpportunityMetricsService
{
    public function __construct(
        private readonly int $cacheTtl = 3600
    ) {}
    
    public function getTeamMetrics(int $teamId): array
    {
        return Cache::remember(
            "team.{$teamId}.opportunity_metrics",
            $this->cacheTtl,
            fn () => $this->calculateMetrics($teamId)
        );
    }
    
    private function calculateMetrics(int $teamId): array
    {
        $opportunities = Opportunity::where('team_id', $teamId)->get();
        
        return [
            'total_value' => $opportunities->sum('value'),
            'average_value' => $opportunities->avg('value'),
            'win_rate' => $this->calculateWinRate($opportunities),
            'pipeline_velocity' => $this->calculateVelocity($opportunities),
        ];
    }
    
    private function calculateWinRate(Collection $opportunities): float
    {
        $total = $opportunities->count();
        if ($total === 0) {
            return 0.0;
        }
        
        $won = $opportunities->where('status', 'won')->count();
        
        return ($won / $total) * 100;
    }
    
    private function calculateVelocity(Collection $opportunities): float
    {
        // Calculate average days to close
        $closed = $opportunities->whereIn('status', ['won', 'lost']);
        
        if ($closed->isEmpty()) {
            return 0.0;
        }
        
        $totalDays = $closed->sum(function ($opp) {
            return $opp->created_at->diffInDays($opp->closed_at ?? now());
        });
        
        return $totalDays / $closed->count();
    }
}
```

### 3. Integration Services

Services that integrate with external systems:

```php
// app/Services/CalendarSyncService.php
namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarSyncService
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly int $timeout = 30
    ) {}
    
    public function syncUserCalendar(User $user): array
    {
        try {
            $response = Http::external('calendar')
                ->timeout($this->timeout)
                ->withToken($user->calendar_token)
                ->get("{$this->apiUrl}/events");
            
            if ($response->failed()) {
                Log::error('Calendar sync failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);
                
                return ['success' => false, 'error' => 'API request failed'];
            }
            
            $events = $response->json('events', []);
            $synced = 0;
            
            foreach ($events as $eventData) {
                $this->syncEvent($user, $eventData);
                $synced++;
            }
            
            return ['success' => true, 'synced' => $synced];
            
        } catch (\Exception $e) {
            Log::error('Calendar sync exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function syncEvent(User $user, array $eventData): void
    {
        CalendarEvent::updateOrCreate(
            [
                'external_id' => $eventData['id'],
                'user_id' => $user->id,
            ],
            [
                'title' => $eventData['title'],
                'start_at' => $eventData['start'],
                'end_at' => $eventData['end'],
                'description' => $eventData['description'] ?? null,
            ]
        );
    }
}
```

## Using Services in Filament Resources

### 1. In Resource Actions

```php
// app/Filament/Resources/OpportunityResource.php
use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        Action::make('viewMetrics')
            ->label(__('app.actions.view_metrics'))
            ->icon('heroicon-o-chart-bar')
            ->modalHeading(__('app.modals.opportunity_metrics'))
            ->modalContent(function () {
                $metricsService = app(OpportunityMetricsService::class);
                $metrics = $metricsService->getTeamMetrics(
                    Filament::getTenant()->id
                );
                
                return view('filament.modals.opportunity-metrics', [
                    'metrics' => $metrics,
                ]);
            }),
    ];
}
```

### 2. In Table Actions

```php
use App\Services\Contact\ContactMergeService;
use Filament\Tables\Actions\Action;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            Action::make('merge')
                ->label(__('app.actions.merge'))
                ->icon('heroicon-o-arrows-right-left')
                ->form([
                    Select::make('duplicate_id')
                        ->label(__('app.labels.merge_with'))
                        ->options(function ($record) {
                            $duplicateService = app(ContactDuplicateDetectionService::class);
                            return $duplicateService->findDuplicates($record)
                                ->pluck('name', 'id');
                        })
                        ->required(),
                ])
                ->action(function (People $record, array $data) {
                    $mergeService = app(ContactMergeService::class);
                    $duplicate = People::find($data['duplicate_id']);
                    
                    $mergeService->merge($record, $duplicate, []);
                    
                    Notification::make()
                        ->title(__('app.notifications.contacts_merged'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ]);
}
```

### 3. In Form Actions

```php
use App\Services\VCardService;

TextInput::make('email')
    ->email()
    ->suffixAction(
        Action::make('importVCard')
            ->icon('heroicon-o-arrow-down-tray')
            ->form([
                FileUpload::make('vcard')
                    ->acceptedFileTypes(['text/vcard', 'text/x-vcard'])
                    ->required(),
            ])
            ->action(function (array $data, $set) {
                $vCardService = app(VCardService::class);
                $contacts = $vCardService->import($data['vcard']);
                
                if ($contacts->isNotEmpty()) {
                    $first = $contacts->first();
                    $set('email', $first['email'] ?? '');
                    $set('name', $first['name'] ?? '');
                }
            })
    )
```

## Testing Services

### 1. Unit Testing with Mocks

```php
// tests/Unit/Services/ContactMergeServiceTest.php
use App\Services\Contact\ContactMergeService;
use App\Services\Contact\ContactDuplicateDetectionService;
use App\Services\AuditLogService;

it('merges contacts and transfers relationships', function () {
    // Mock dependencies
    $duplicateDetection = Mockery::mock(ContactDuplicateDetectionService::class);
    $auditLog = Mockery::mock(AuditLogService::class);
    
    $auditLog->shouldReceive('logMerge')
        ->once()
        ->andReturn(true);
    
    // Create service with mocked dependencies
    $service = new ContactMergeService($duplicateDetection, $auditLog);
    
    // Create test data
    $primary = People::factory()->create(['name' => 'John Doe']);
    $duplicate = People::factory()->create(['name' => 'Jon Doe']);
    
    Task::factory()->create(['people_id' => $duplicate->id]);
    
    // Execute merge
    $result = $service->merge($primary, $duplicate, []);
    
    // Assert
    expect($result->id)->toBe($primary->id);
    expect($primary->tasks)->toHaveCount(1);
    expect(People::find($duplicate->id))->toBeNull();
});
```

### 2. Feature Testing with Real Services

```php
// tests/Feature/Services/OpportunityMetricsServiceTest.php
use App\Services\Opportunities\OpportunityMetricsService;

it('calculates team metrics correctly', function () {
    $team = Team::factory()->create();
    
    // Create test opportunities
    Opportunity::factory()->create([
        'team_id' => $team->id,
        'value' => 10000,
        'status' => 'won',
    ]);
    
    Opportunity::factory()->create([
        'team_id' => $team->id,
        'value' => 5000,
        'status' => 'lost',
    ]);
    
    // Get metrics
    $service = app(OpportunityMetricsService::class);
    $metrics = $service->getTeamMetrics($team->id);
    
    // Assert
    expect($metrics['total_value'])->toBe(15000.0);
    expect($metrics['average_value'])->toBe(7500.0);
    expect($metrics['win_rate'])->toBe(50.0);
});
```

### 3. Testing with Fake Services

```php
// tests/Feature/CalendarSyncTest.php
use App\Services\CalendarSyncService;
use Illuminate\Support\Facades\Http;

it('syncs calendar events from external API', function () {
    Http::fake([
        'calendar.example.com/*' => Http::response([
            'events' => [
                [
                    'id' => 'ext-123',
                    'title' => 'Meeting',
                    'start' => now()->toIso8601String(),
                    'end' => now()->addHour()->toIso8601String(),
                ],
            ],
        ], 200),
    ]);
    
    $user = User::factory()->create(['calendar_token' => 'test-token']);
    $service = app(CalendarSyncService::class);
    
    $result = $service->syncUserCalendar($user);
    
    expect($result['success'])->toBeTrue();
    expect($result['synced'])->toBe(1);
    expect($user->calendarEvents)->toHaveCount(1);
});
```

## Best Practices

### 1. Constructor Injection

Always use constructor injection for dependencies:

```php
// ✅ GOOD
class InvoiceService
{
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly NotificationService $notificationService
    ) {}
}

// ❌ BAD
class InvoiceService
{
    public function generatePdf($invoice)
    {
        $pdfService = app(PdfService::class); // Don't do this
    }
}
```

### 2. Single Responsibility

Each service should have one clear purpose:

```php
// ✅ GOOD - Focused services
class ContactMergeService { /* handles merging */ }
class ContactDuplicateDetectionService { /* handles detection */ }
class ContactExportService { /* handles exports */ }

// ❌ BAD - God service
class ContactService {
    public function merge() {}
    public function detectDuplicates() {}
    public function export() {}
    public function import() {}
    public function sendEmail() {}
    // Too many responsibilities
}
```

### 3. Interface Segregation

Use interfaces for flexibility:

```php
// ✅ GOOD
interface PaymentProcessorInterface {
    public function charge(float $amount): PaymentResult;
}

class StripeProcessor implements PaymentProcessorInterface {}
class PayPalProcessor implements PaymentProcessorInterface {}

// ❌ BAD - Concrete dependency
class OrderService {
    public function __construct(private StripeProcessor $processor) {}
}
```

### 4. Immutability

Prefer readonly properties and return new instances:

```php
// ✅ GOOD
class MetricsService
{
    public function __construct(
        private readonly int $cacheTtl,
        private readonly CacheInterface $cache
    ) {}
}

// ❌ BAD
class MetricsService
{
    private $cacheTtl;
    
    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl; // Mutable state
    }
}
```

### 5. Error Handling

Handle errors gracefully and log appropriately:

```php
class ExternalApiService
{
    public function fetchData(): array
    {
        try {
            $response = Http::external()->get('/data');
            
            if ($response->failed()) {
                Log::warning('API request failed', [
                    'status' => $response->status(),
                ]);
                
                return [];
            }
            
            return $response->json();
            
        } catch (\Exception $e) {
            Log::error('API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new ApiException('Failed to fetch data', 0, $e);
        }
    }
}
```

## Service Organization

### Directory Structure

```
app/Services/
├── AI/
│   └── CodebaseIntrospectionService.php
├── Contact/
│   ├── ContactDuplicateDetectionService.php
│   ├── ContactMergeService.php
│   ├── PortalAccessService.php
│   └── VCardService.php
├── Opportunities/
│   ├── OpportunityMetricsService.php
│   └── OpportunityStageService.php
├── Search/
│   ├── GlobalSearchService.php
│   └── SavedSearchService.php
└── Tenancy/
    └── CurrentTeamResolver.php
```

### Naming Conventions

- Service classes end with `Service`: `ContactMergeService`
- Action-focused services use verbs: `LeadConversionService`
- Query-focused services use nouns: `OpportunityMetricsService`
- Integration services use the system name: `GitHubService`, `CalendarSyncService`

## Common Patterns

### 1. Service with Configuration

```php
class EmailService
{
    public function __construct(
        private readonly string $fromAddress,
        private readonly string $fromName,
        private readonly bool $queueEmails = true
    ) {}
    
    public static function fromConfig(): self
    {
        return new self(
            config('mail.from.address'),
            config('mail.from.name'),
            config('mail.queue', true)
        );
    }
}

// Register in AppServiceProvider
$this->app->singleton(EmailService::class, function () {
    return EmailService::fromConfig();
});
```

### 2. Service with Events

```php
class OrderService
{
    public function __construct(
        private readonly EventDispatcher $events
    ) {}
    
    public function createOrder(array $data): Order
    {
        $order = Order::create($data);
        
        $this->events->dispatch(new OrderCreated($order));
        
        return $order;
    }
}
```

### 3. Service with Caching

```php
class ProductCatalogService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600
    ) {}
    
    public function getProducts(int $categoryId): Collection
    {
        return $this->cache->remember(
            "products.category.{$categoryId}",
            $this->ttl,
            fn () => Product::where('category_id', $categoryId)->get()
        );
    }
    
    public function clearCache(int $categoryId): void
    {
        $this->cache->forget("products.category.{$categoryId}");
    }
}
```

## Integration with Existing Patterns

### Works With

- **HTTP Client Macros**: Use `Http::external()` in services (see `docs/laravel-http-client.md`)
- **Array Helpers**: Use `ArrayHelper` for data formatting (see `docs/array-helpers.md`)
- **Date Scopes**: Services can leverage model date scopes (see `docs/laravel-date-scopes.md`)
- **Filament Actions**: Services power Filament resource actions
- **Queue Jobs**: Services can be called from queued jobs

### Example Integration

```php
class ReportGenerationService
{
    public function __construct(
        private readonly OpportunityMetricsService $metrics,
        private readonly PdfService $pdf,
        private readonly NotificationService $notifications
    ) {}
    
    public function generateMonthlyReport(Team $team): string
    {
        // Use metrics service
        $metrics = $this->metrics->getTeamMetrics($team->id);
        
        // Format with array helper
        $formattedData = ArrayHelper::joinList(
            $metrics['top_performers'],
            ', ',
            ' and '
        );
        
        // Generate PDF
        $pdfPath = $this->pdf->generate('reports.monthly', [
            'team' => $team,
            'metrics' => $metrics,
            'performers' => $formattedData,
        ]);
        
        // Send notification
        $this->notifications->send($team->owner, [
            'title' => __('app.notifications.report_ready'),
            'body' => __('app.notifications.report_generated'),
            'action_url' => route('reports.download', $pdfPath),
        ]);
        
        return $pdfPath;
    }
}
```

## References

- [Laravel Service Container Documentation](https://laravel.com/docs/container)
- [Dependency Injection in Laravel](https://laravel.com/docs/providers)
- [Testing Laravel Services](https://laravel.com/docs/testing)
- Related docs: `laravel-http-client.md`, `array-helpers.md`, `testing-infrastructure.md`
