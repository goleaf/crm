# Laravel Service Container Integration Guide

## Overview

This guide covers comprehensive service container integration patterns for this CRM application, focusing on dependency injection, service registration, and Filament v4.3+ integration.

## Table of Contents

1. [Core Concepts](#core-concepts)
2. [Service Registration](#service-registration)
3. [Dependency Injection Patterns](#dependency-injection-patterns)
4. [Filament Integration](#filament-integration)
5. [Testing Services](#testing-services)
6. [Performance Optimization](#performance-optimization)
7. [Common Patterns](#common-patterns)

## Core Concepts

### The Service Container

Laravel's service container is a powerful tool for managing class dependencies and performing dependency injection. It automatically resolves dependencies through constructor injection, eliminating the need for manual instantiation.

**Key Benefits:**
- Automatic dependency resolution
- Interface-based programming
- Testability through mocking
- Centralized configuration
- Lazy loading of services

### Binding Types

```php
// Transient - New instance every time
$this->app->bind(ReportGenerator::class);

// Singleton - Shared instance
$this->app->singleton(CacheManager::class);

// Scoped - Shared within request/job
$this->app->scoped(RequestContext::class);

// Instance - Bind existing instance
$this->app->instance(Config::class, $config);
```

## Service Registration

### AppServiceProvider

Register all application services in `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Services\Contact\ContactMergeService;
use App\Services\Contact\DuplicateDetectionService;
use App\Services\AI\RecordSummaryService;
use App\Services\Export\ExportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton services (shared state)
        $this->app->singleton(DuplicateDetectionService::class);
        $this->app->singleton(RecordSummaryService::class);
        
        // Transient services (fresh instance)
        $this->app->bind(ContactMergeService::class);
        $this->app->bind(ExportService::class);
        
        // Interface bindings
        $this->app->bind(
            \App\Contracts\PaymentProcessorInterface::class,
            \App\Services\Payment\StripePaymentProcessor::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
```

### Domain-Specific Service Providers

For large applications, create domain-specific providers:

```php
<?php

namespace App\Providers;

use App\Services\Contact\ContactMergeService;
use App\Services\Contact\DuplicateDetectionService;
use App\Services\Contact\VCardService;
use App\Services\Contact\PortalAccessService;
use Illuminate\Support\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DuplicateDetectionService::class, function ($app) {
            return new DuplicateDetectionService(
                similarityThreshold: config('contacts.duplicate_threshold', 0.75),
                cacheEnabled: config('contacts.cache_duplicates', true)
            );
        });
        
        $this->app->bind(ContactMergeService::class);
        $this->app->bind(VCardService::class);
        $this->app->bind(PortalAccessService::class);
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

## Dependency Injection Patterns

### Constructor Injection (Preferred)

Always use constructor injection with readonly properties:

```php
<?php

namespace App\Services\Contact;

use App\Services\AI\RecordSummaryService;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;

class ContactMergeService
{
    public function __construct(
        private readonly DuplicateDetectionService $duplicateDetection,
        private readonly AuditLogService $auditLog,
        private readonly RecordSummaryService $summaryService
    ) {}
    
    public function merge(People $primary, People $duplicate, array $fieldSelections): People
    {
        return DB::transaction(function () use ($primary, $duplicate, $fieldSelections) {
            // Merge logic using injected services
            $this->transferRelationships($duplicate, $primary);
            $this->auditLog->logMerge($primary, $duplicate);
            $this->summaryService->regenerateSummary($primary);
            
            $duplicate->delete();
            
            return $primary->fresh();
        });
    }
    
    private function transferRelationships(People $from, People $to): void
    {
        // Transfer tasks, notes, opportunities
        $from->tasks()->update(['people_id' => $to->id]);
        $from->notes()->update(['people_id' => $to->id]);
        $from->opportunities()->update(['people_id' => $to->id]);
    }
}
```

### Method Injection

Use method injection for optional dependencies or when you need different implementations per call:

```php
public function generateReport(ReportBuilder $builder): Report
{
    return $builder
        ->setData($this->getData())
        ->setFormat('pdf')
        ->generate();
}
```

### Contextual Binding

Bind different implementations based on context:

```php
$this->app->when(ContactMergeService::class)
    ->needs(AuditLogService::class)
    ->give(function () {
        return new AuditLogService(
            channel: 'contact_merges',
            level: 'info'
        );
    });

$this->app->when(OpportunityService::class)
    ->needs(AuditLogService::class)
    ->give(function () {
        return new AuditLogService(
            channel: 'opportunities',
            level: 'debug'
        );
    });
```

## Filament Integration

### In Resource Actions

Inject services in action callbacks:

```php
<?php

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Services\Contact\ContactMergeService;
use App\Services\Contact\DuplicateDetectionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ViewPeople extends ViewRecord
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('findDuplicates')
                ->label(__('app.actions.find_duplicates'))
                ->icon('heroicon-o-document-duplicate')
                ->action(function (DuplicateDetectionService $service) {
                    $duplicates = $service->findDuplicates($this->record);
                    
                    if ($duplicates->isEmpty()) {
                        Notification::make()
                            ->title(__('app.notifications.no_duplicates_found'))
                            ->success()
                            ->send();
                        return;
                    }
                    
                    // Show duplicates modal
                    $this->dispatch('show-duplicates', duplicates: $duplicates);
                }),
                
            Action::make('merge')
                ->label(__('app.actions.merge_contact'))
                ->icon('heroicon-o-arrows-pointing-in')
                ->form([
                    Select::make('duplicate_id')
                        ->label(__('app.labels.duplicate_contact'))
                        ->options(fn () => People::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data, ContactMergeService $service) {
                    $duplicate = People::findOrFail($data['duplicate_id']);
                    
                    try {
                        $service->merge($this->record, $duplicate, []);
                        
                        Notification::make()
                            ->title(__('app.notifications.contacts_merged'))
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.app.resources.people.view', $this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.merge_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.merge_contacts'))
                ->modalDescription(__('app.modals.merge_contacts_description'))
                ->color('warning'),
        ];
    }
}
```

### In Table Actions

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
                    
                    return response()->streamDownload(function () use ($vcard) {
                        echo $vcard;
                    }, "{$record->name}.vcf", [
                        'Content-Type' => 'text/vcard',
                    ]);
                }),
        ]);
}
```

### In Form Components

```php
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

### In Widgets

```php
<?php

namespace App\Filament\Widgets;

use App\Services\Metrics\OpportunityMetricsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpportunityStatsWidget extends BaseWidget
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
        
        return [
            Stat::make(__('app.labels.total_value'), $metrics['total_value'])
                ->description($metrics['change_percentage'] . '% from last month')
                ->descriptionIcon($metrics['trend_icon'])
                ->color($metrics['trend_color']),
                
            Stat::make(__('app.labels.win_rate'), $metrics['win_rate'] . '%')
                ->description(__('app.labels.closed_won'))
                ->color('success'),
        ];
    }
}
```

## Testing Services

### Unit Tests with Mocking

```php
<?php

use App\Services\Contact\ContactMergeService;
use App\Services\Contact\DuplicateDetectionService;
use App\Services\Audit\AuditLogService;
use App\Services\AI\RecordSummaryService;
use App\Models\People;

it('merges contacts and transfers all relationships', function () {
    // Mock dependencies
    $duplicateDetection = Mockery::mock(DuplicateDetectionService::class);
    $auditLog = Mockery::mock(AuditLogService::class);
    $summaryService = Mockery::mock(RecordSummaryService::class);
    
    // Set expectations
    $auditLog->shouldReceive('logMerge')->once();
    $summaryService->shouldReceive('regenerateSummary')->once();
    
    // Create service with mocked dependencies
    $service = new ContactMergeService(
        $duplicateDetection,
        $auditLog,
        $summaryService
    );
    
    // Create test data
    $primary = People::factory()->create();
    $duplicate = People::factory()->create();
    
    Task::factory()->create(['people_id' => $duplicate->id]);
    Note::factory()->create(['people_id' => $duplicate->id]);
    
    // Execute
    $result = $service->merge($primary, $duplicate, []);
    
    // Assert
    expect($result->id)->toBe($primary->id);
    expect($primary->tasks)->toHaveCount(1);
    expect($primary->notes)->toHaveCount(1);
    expect(People::withTrashed()->find($duplicate->id)->trashed())->toBeTrue();
});
```

### Feature Tests with Real Dependencies

```php
it('detects duplicate contacts based on email similarity', function () {
    $team = Team::factory()->create();
    
    $contact1 = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Smith',
        'email' => 'john.smith@example.com',
    ]);
    
    $contact2 = People::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jon Smith',
        'email' => 'john.smith@example.com',
    ]);
    
    // Resolve service from container
    $service = app(DuplicateDetectionService::class);
    
    $duplicates = $service->findDuplicates($contact1);
    
    expect($duplicates)->toHaveCount(1);
    expect($duplicates->first()->id)->toBe($contact2->id);
});
```

### Integration Tests

```php
it('exports contact to vCard format', function () {
    $contact = People::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+1234567890',
    ]);
    
    $service = app(VCardService::class);
    $vcard = $service->export($contact);
    
    expect($vcard)->toContain('BEGIN:VCARD');
    expect($vcard)->toContain('FN:Jane Doe');
    expect($vcard)->toContain('EMAIL:jane@example.com');
    expect($vcard)->toContain('TEL:+1234567890');
    expect($vcard)->toContain('END:VCARD');
});
```

## Performance Optimization

### Caching Service Results

```php
<?php

namespace App\Services\Metrics;

use Illuminate\Support\Facades\Cache;

class OpportunityMetricsService
{
    public function __construct(
        private readonly int $cacheTtl = 3600
    ) {}
    
    public function getTeamMetrics(int $teamId): array
    {
        return Cache::remember(
            "metrics.opportunities.team.{$teamId}",
            $this->cacheTtl,
            fn () => $this->calculateMetrics($teamId)
        );
    }
    
    private function calculateMetrics(int $teamId): array
    {
        // Expensive calculations
        return [
            'total_value' => Opportunity::where('team_id', $teamId)->sum('value'),
            'win_rate' => $this->calculateWinRate($teamId),
            'trend_icon' => 'heroicon-o-arrow-trending-up',
            'trend_color' => 'success',
        ];
    }
    
    public function clearCache(int $teamId): void
    {
        Cache::forget("metrics.opportunities.team.{$teamId}");
    }
}
```

### Lazy Loading Services

```php
// Only instantiate when actually used
$this->app->singleton(HeavyService::class, function ($app) {
    return new HeavyService(
        // Expensive initialization
    );
});
```

### Eager Loading Relationships

```php
public function getContactsWithRelationships(int $teamId): Collection
{
    return People::where('team_id', $teamId)
        ->with(['tasks', 'notes', 'opportunities', 'company'])
        ->get();
}
```

## Common Patterns

### Service with Configuration

```php
<?php

namespace App\Services\Contact;

class DuplicateDetectionService
{
    public function __construct(
        private readonly float $similarityThreshold = 0.75,
        private readonly bool $cacheEnabled = true,
        private readonly int $cacheTtl = 3600
    ) {}
    
    public static function fromConfig(): self
    {
        return new self(
            similarityThreshold: config('contacts.duplicate_threshold', 0.75),
            cacheEnabled: config('contacts.cache_duplicates', true),
            cacheTtl: config('contacts.cache_ttl', 3600)
        );
    }
    
    public function findDuplicates(People $contact): Collection
    {
        if (!$this->cacheEnabled) {
            return $this->detectDuplicates($contact);
        }
        
        return Cache::remember(
            "duplicates.{$contact->id}",
            $this->cacheTtl,
            fn () => $this->detectDuplicates($contact)
        );
    }
}
```

### Service with Events

```php
<?php

namespace App\Services\Contact;

use App\Events\ContactMerged;
use Illuminate\Contracts\Events\Dispatcher;

class ContactMergeService
{
    public function __construct(
        private readonly Dispatcher $events
    ) {}
    
    public function merge(People $primary, People $duplicate, array $fieldSelections): People
    {
        // Merge logic
        
        $this->events->dispatch(new ContactMerged($primary, $duplicate));
        
        return $primary;
    }
}
```

### Service with HTTP Client

```php
<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 10
    ) {}
    
    public function verify(string $email): object
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->withToken($this->apiKey)
                ->get('https://api.emailverification.com/verify', [
                    'email' => $email,
                ]);
            
            if ($response->successful()) {
                return (object) [
                    'isValid' => $response->json('valid', false),
                    'reason' => $response->json('reason'),
                ];
            }
            
            Log::warning('Email verification API failed', [
                'email' => $email,
                'status' => $response->status(),
            ]);
            
            return (object) ['isValid' => false, 'reason' => 'API error'];
            
        } catch (\Exception $e) {
            Log::error('Email verification exception', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            
            return (object) ['isValid' => false, 'reason' => 'Service unavailable'];
        }
    }
}
```

### Repository Pattern

```php
<?php

namespace App\Repositories;

use App\Models\People;
use Illuminate\Database\Eloquent\Collection;

interface ContactRepositoryInterface
{
    public function findById(int $id): ?People;
    public function findByEmail(string $email): ?People;
    public function search(string $query, int $teamId): Collection;
    public function create(array $data): People;
    public function update(People $contact, array $data): People;
    public function delete(People $contact): bool;
}

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function findById(int $id): ?People
    {
        return People::find($id);
    }
    
    public function findByEmail(string $email): ?People
    {
        return People::where('email', $email)->first();
    }
    
    public function search(string $query, int $teamId): Collection
    {
        return People::where('team_id', $teamId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->get();
    }
    
    public function create(array $data): People
    {
        return People::create($data);
    }
    
    public function update(People $contact, array $data): People
    {
        $contact->update($data);
        return $contact->fresh();
    }
    
    public function delete(People $contact): bool
    {
        return $contact->delete();
    }
}

// Register in AppServiceProvider
$this->app->bind(
    ContactRepositoryInterface::class,
    EloquentContactRepository::class
);
```

## Best Practices

### DO:
- ✅ Use constructor injection for all dependencies
- ✅ Register services in AppServiceProvider or domain providers
- ✅ Use interfaces for swappable implementations
- ✅ Keep services focused on single responsibility
- ✅ Use readonly properties for immutability (PHP 8.4+)
- ✅ Handle errors gracefully with try-catch
- ✅ Log errors with context (user, action, data)
- ✅ Return typed results (DTOs, arrays, models)
- ✅ Use transactions for multi-step operations
- ✅ Cache expensive operations with appropriate TTL
- ✅ Write unit tests with mocked dependencies
- ✅ Write feature tests with real dependencies

### DON'T:
- ❌ Use service locator pattern (`app()`, `resolve()`) in business logic
- ❌ Create god services with too many responsibilities
- ❌ Forget to register services in container
- ❌ Use concrete classes when interfaces would be better
- ❌ Ignore error handling and logging
- ❌ Skip testing service logic
- ❌ Mix presentation logic with business logic
- ❌ Use mutable state in services
- ❌ Hardcode configuration values
- ❌ Forget to clear caches when data changes

## Related Documentation

- [Laravel Container Services](./laravel-container-services.md)
- [Filament v4 Conventions](../.kiro/steering/filament-conventions.md)
- [Testing Standards](../.kiro/steering/testing-standards.md)
- [Laravel HTTP Client](./laravel-http-client.md)
- [Laravel Precognition](./laravel-precognition.md)

## References

- [Laravel Service Container Documentation](https://laravel.com/docs/container)
- [Laravel Service Providers Documentation](https://laravel.com/docs/providers)
- [Dependency Injection in PHP](https://phptherightway.com/#dependency_injection)
