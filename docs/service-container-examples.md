# Service Container Integration Examples

## Overview

This document provides practical examples of service container integration throughout this CRM application, demonstrating patterns for Filament v4.3+, testing, and real-world use cases.

## Table of Contents

1. [Service Registration Examples](#service-registration-examples)
2. [Filament Resource Integration](#filament-resource-integration)
3. [Filament Widget Integration](#filament-widget-integration)
4. [Testing Patterns](#testing-patterns)
5. [Real-World Use Cases](#real-world-use-cases)

## Service Registration Examples

### AppServiceProvider Registration

```php
<?php

namespace App\Providers;

use App\Contracts\Repositories\ExampleRepositoryInterface;
use App\Repositories\EloquentExampleRepository;
use App\Services\Example\ExampleActionService;
use App\Services\Example\ExampleIntegrationService;
use App\Services\Example\ExampleQueryService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton services (shared state, caching)
        $this->app->singleton(ExampleQueryService::class, function ($app) {
            return new ExampleQueryService(
                cacheTtl: (int) config('cache.ttl.metrics', 3600)
            );
        });
        
        $this->app->singleton(ExampleIntegrationService::class, function ($app) {
            return ExampleIntegrationService::fromConfig();
        });
        
        // Transient services (fresh instance per resolution)
        $this->app->bind(ExampleActionService::class);
        
        // Interface bindings (repository pattern)
        $this->app->bind(
            ExampleRepositoryInterface::class,
            EloquentExampleRepository::class
        );
    }
}
```

### Domain-Specific Service Provider

```php
<?php

namespace App\Providers;

use App\Services\Contacts\ContactDuplicateDetectionService;
use App\Services\Contacts\ContactMergeService;
use App\Services\Contacts\PortalAccessService;
use App\Services\Contacts\VCardService;
use Illuminate\Support\ServiceProvider;

final class ContactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Duplicate detection with configuration
        $this->app->singleton(ContactDuplicateDetectionService::class, function ($app) {
            return new ContactDuplicateDetectionService(
                similarityThreshold: config('contacts.duplicate_threshold', 0.75),
                cacheEnabled: config('contacts.cache_duplicates', true)
            );
        });
        
        // Transient services
        $this->app->bind(ContactMergeService::class);
        $this->app->bind(VCardService::class);
        $this->app->bind(PortalAccessService::class);
    }
    
    public function boot(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/contacts.php',
            'contacts'
        );
    }
}
```

Register in `config/app.php`:

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ...
    App\Providers\ContactServiceProvider::class,
])->toArray(),
```

## Filament Resource Integration

### Resource Actions with Service Injection

```php
<?php

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Services\Contacts\ContactDuplicateDetectionService;
use App\Services\Contacts\ContactMergeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class ViewPeople extends ViewRecord
{
    protected function getHeaderActions(): array
    {
        return [
            // Service injected via method parameter
            Action::make('findDuplicates')
                ->label(__('app.actions.find_duplicates'))
                ->action(function (ContactDuplicateDetectionService $service) {
                    $duplicates = $service->findDuplicates($this->record);
                    
                    if ($duplicates->isEmpty()) {
                        Notification::make()
                            ->title(__('app.notifications.no_duplicates_found'))
                            ->success()
                            ->send();
                        return;
                    }
                    
                    // Handle duplicates...
                }),
                
            Action::make('merge')
                ->form([/* ... */])
                ->action(function (array $data, ContactMergeService $service) {
                    $duplicate = People::findOrFail($data['duplicate_id']);
                    
                    try {
                        $service->merge($this->record, $duplicate, auth()->id());
                        
                        Notification::make()
                            ->title(__('app.notifications.contacts_merged'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.merge_failed'))
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }
}
```

### Table Actions with Service Injection

```php
public static function table(Table $table): Table
{
    return $table
        ->actions([
            Action::make('exportVCard')
                ->label(__('app.actions.export_vcard'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (People $record, VCardService $service) {
                    $vcard = $service->export($record);
                    
                    return response()->streamDownload(
                        fn () => print $vcard,
                        "{$record->name}.vcf",
                        ['Content-Type' => 'text/vcard']
                    );
                }),
                
            Action::make('grantPortalAccess')
                ->label(__('app.actions.grant_portal_access'))
                ->icon('heroicon-o-key')
                ->action(function (People $record, PortalAccessService $service) {
                    try {
                        $portalUser = $service->grantAccess($record);
                        
                        Notification::make()
                            ->title(__('app.notifications.portal_access_granted'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.portal_access_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->visible(fn (People $record) => !$record->portalUser),
        ]);
}
```

### Form Actions with Service Injection

```php
use App\Services\External\EmailVerificationService;

TextInput::make('email')
    ->email()
    ->required()
    ->suffixAction(
        Action::make('verifyEmail')
            ->icon('heroicon-o-check-badge')
            ->action(function ($state, $set, EmailVerificationService $service) {
                $result = $service->verify($state);
                
                if ($result->isValid) {
                    $set('email_verified', true);
                    $set('email_verified_at', now());
                    
                    Notification::make()
                        ->title(__('app.notifications.email_verified'))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title(__('app.notifications.email_invalid'))
                        ->body($result->reason)
                        ->warning()
                        ->send();
                }
            })
    )
```

## Filament Widget Integration

### Stats Widget with Service Injection

```php
<?php

namespace App\Filament\Widgets;

use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class OpportunityStatsWidget extends BaseWidget
{
    public function __construct(
        private readonly OpportunityMetricsService $metricsService
    ) {
        parent::__construct();
    }
    
    protected function getStats(): array
    {
        $teamId = Filament::getTenant()->id;
        $metrics = $this->metricsService->getTeamMetrics($teamId);
        
        return [
            Stat::make(__('app.labels.total_value'), $metrics['total_value_formatted'])
                ->description($metrics['change_percentage'].'% from last month')
                ->descriptionIcon($metrics['trend_icon'])
                ->color($metrics['trend_color']),
                
            Stat::make(__('app.labels.win_rate'), $metrics['win_rate'].'%')
                ->description(__('app.labels.closed_won'))
                ->color('success'),
        ];
    }
}
```

### Chart Widget with Service Injection

```php
<?php

namespace App\Filament\Widgets;

use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Widgets\ChartWidget;

final class OpportunityTrendChart extends ChartWidget
{
    public function __construct(
        private readonly OpportunityMetricsService $metricsService
    ) {
        parent::__construct();
    }
    
    protected function getData(): array
    {
        $data = $this->metricsService->getTrendData(
            teamId: Filament::getTenant()->id,
            months: 6
        );
        
        return [
            'datasets' => [
                [
                    'label' => __('app.labels.opportunity_value'),
                    'data' => $data['values'],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
```

## Testing Patterns

### Unit Tests with Mocked Services

```php
<?php

use App\Models\People;
use App\Services\ActivityService;
use App\Services\Example\ExampleActionService;
use App\Services\Example\ExampleQueryService;

it('performs operation with mocked dependencies', function () {
    // Arrange - Mock dependencies
    $activityService = Mockery::mock(ActivityService::class);
    $queryService = Mockery::mock(ExampleQueryService::class);
    
    // Set expectations
    $queryService->shouldReceive('getContactMetrics')
        ->once()
        ->andReturn(['total_tasks' => 5]);
    
    $activityService->shouldReceive('log')
        ->once()
        ->with(
            Mockery::type(People::class),
            'Contact updated',
            Mockery::type('array')
        );
    
    // Act - Create service with mocks
    $service = new ExampleActionService($activityService, $queryService);
    $contact = People::factory()->create();
    $result = $service->performComplexOperation($contact, ['name' => 'New Name']);
    
    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['contact']->name)->toBe('New Name');
});
```

### Feature Tests with Real Services

```php
<?php

use App\Models\People;
use App\Services\Contacts\ContactDuplicateDetectionService;

it('detects duplicate contacts', function () {
    // Arrange
    $team = Team::factory()->create();
    
    $contact1 = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Smith',
        'primary_email' => 'john.smith@example.com',
    ]);
    
    $contact2 = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jon Smith',
        'primary_email' => 'john.smith@example.com',
    ]);
    
    // Act - Resolve service from container
    $service = app(ContactDuplicateDetectionService::class);
    $duplicates = $service->findDuplicates($contact1);
    
    // Assert
    expect($duplicates)->toHaveCount(1);
    expect($duplicates->first()['contact']->id)->toBe($contact2->id);
    expect($duplicates->first()['score'])->toBeGreaterThan(0.75);
});
```

### Integration Tests with HTTP Fakes

```php
<?php

use App\Services\Example\ExampleIntegrationService;
use Illuminate\Support\Facades\Http;

it('fetches data from external API', function () {
    // Arrange
    Http::fake([
        'https://api.example.com/*' => Http::response([
            'status' => 'success',
            'data' => ['key' => 'value'],
        ], 200),
    ]);
    
    // Act
    $service = app(ExampleIntegrationService::class);
    $result = $service->fetchData('https://api.example.com/data');
    
    // Assert
    expect($result)->toBeArray();
    expect($result['status'])->toBe('success');
    
    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization')
            && $request->url() === 'https://api.example.com/data';
    });
});
```

## Real-World Use Cases

### Contact Merge Workflow

```php
// Service: app/Services/Contacts/ContactMergeService.php
final class ContactMergeService
{
    public function merge(People $primary, People $duplicate, int $userId): People
    {
        return DB::transaction(function () use ($primary, $duplicate, $userId) {
            // Transfer relationships
            $this->transferRelation($duplicate, $primary, 'tasks');
            $this->transferRelation($duplicate, $primary, 'notes');
            $this->transferRelation($duplicate, $primary, 'opportunities');
            
            // Log merge
            ContactMergeLog::create([
                'primary_contact_id' => $primary->id,
                'duplicate_contact_id' => $duplicate->id,
                'merged_by' => $userId,
            ]);
            
            // Soft delete duplicate
            $duplicate->delete();
            
            return $primary->fresh();
        });
    }
}

// Filament Action
Action::make('merge')
    ->action(function (array $data, ContactMergeService $service) {
        $duplicate = People::findOrFail($data['duplicate_id']);
        $service->merge($this->record, $duplicate, auth()->id());
        
        Notification::make()
            ->title('Contacts merged successfully')
            ->success()
            ->send();
    });
```

### Duplicate Detection with Caching

```php
// Service: app/Services/Contacts/ContactDuplicateDetectionService.php
final class ContactDuplicateDetectionService
{
    public function __construct(
        private readonly float $similarityThreshold = 0.75,
        private readonly bool $cacheEnabled = true
    ) {}
    
    public function findDuplicates(People $contact): Collection
    {
        if (!$this->cacheEnabled) {
            return $this->detectDuplicates($contact);
        }
        
        return Cache::remember(
            "duplicates.{$contact->id}",
            3600,
            fn () => $this->detectDuplicates($contact)
        );
    }
}

// Filament Action
Action::make('findDuplicates')
    ->action(function (ContactDuplicateDetectionService $service) {
        $duplicates = $service->findDuplicates($this->record);
        
        if ($duplicates->isEmpty()) {
            Notification::make()
                ->title('No duplicates found')
                ->success()
                ->send();
        }
    });
```

### External API Integration

```php
// Service: app/Services/Example/ExampleIntegrationService.php
final readonly class ExampleIntegrationService
{
    public function __construct(
        private string $apiKey,
        private int $timeout = 10
    ) {}
    
    public function fetchData(string $endpoint): ?array
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->retry(3, 100)
                ->withToken($this->apiKey)
                ->get($endpoint);
            
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('API request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

// Filament Action
Action::make('syncData')
    ->action(function (ExampleIntegrationService $service) {
        $data = $service->fetchData('https://api.example.com/contacts');
        
        if ($data) {
            // Process data...
            Notification::make()
                ->title('Data synced successfully')
                ->success()
                ->send();
        }
    });
```

## Best Practices Summary

### DO:
- ✅ Use constructor injection with readonly properties
- ✅ Register services in AppServiceProvider or domain providers
- ✅ Inject services via method parameters in Filament actions
- ✅ Use singleton for services with caching or shared state
- ✅ Use transient (bind) for stateless action services
- ✅ Mock dependencies in unit tests
- ✅ Use real services in feature tests
- ✅ Handle errors gracefully with try-catch
- ✅ Log errors with context
- ✅ Cache expensive operations

### DON'T:
- ❌ Use service locator pattern (`app()`, `resolve()`) in business logic
- ❌ Create god services with too many responsibilities
- ❌ Forget to register services in container
- ❌ Skip error handling
- ❌ Ignore testing
- ❌ Mix presentation and business logic
- ❌ Use mutable state in services
- ❌ Hardcode configuration values

## Related Documentation

- [Laravel Service Container Integration](./laravel-service-container-integration.md)
- [Laravel Container Services](./laravel-container-services.md)
- [Filament v4.3+ Conventions](../.kiro/steering/filament-conventions.md)
- [Testing Standards](../.kiro/steering/testing-standards.md)
