# Laravel Pipeline Integration

## Overview
Laravel Pipelines provide a clean way to pass data through a series of processing stages (pipes). This document covers enhanced pipeline patterns for the Relaticle CRM application.

## Core Concepts

### What is a Pipeline?
A pipeline takes an input, passes it through a series of "pipes" (classes or closures), and returns the final result. Each pipe can transform, validate, or enrich the data.

### Benefits
- **Separation of Concerns**: Each pipe handles one responsibility
- **Reusability**: Pipes can be reused across different pipelines
- **Testability**: Each pipe can be tested in isolation
- **Maintainability**: Easy to add, remove, or reorder processing steps
- **Readability**: Clear, linear flow of data transformations

## Basic Usage

### Simple Pipeline
```php
use Illuminate\Pipeline\Pipeline;

$result = app(Pipeline::class)
    ->send($data)
    ->through([
        ValidateData::class,
        EnrichData::class,
        TransformData::class,
    ])
    ->thenReturn();
```

### Pipeline with Closure
```php
$result = app(Pipeline::class)
    ->send($user)
    ->through([
        function ($user, $next) {
            $user->validated = true;
            return $next($user);
        },
        function ($user, $next) {
            $user->enriched = true;
            return $next($user);
        },
    ])
    ->thenReturn();
```

## Service Pattern Integration

### Pipeline Service
```php
namespace App\Services\Pipeline;

use Illuminate\Pipeline\Pipeline;

class PipelineService
{
    public function __construct(
        private readonly Pipeline $pipeline
    ) {}
    
    public function process(mixed $data, array $pipes): mixed
    {
        return $this->pipeline
            ->send($data)
            ->through($pipes)
            ->thenReturn();
    }
    
    public function processWithCallback(mixed $data, array $pipes, callable $callback): mixed
    {
        return $this->pipeline
            ->send($data)
            ->through($pipes)
            ->then($callback);
    }
}
```

### Register in AppServiceProvider
```php
public function register(): void
{
    $this->app->singleton(PipelineService::class, function ($app) {
        return new PipelineService($app->make(Pipeline::class));
    });
}
```

## Common Use Cases

### 1. Data Import Pipeline
```php
namespace App\Services\Import;

use App\Services\Pipeline\PipelineService;
use App\Pipes\Import\ValidateImportData;
use App\Pipes\Import\TransformImportData;
use App\Pipes\Import\SaveImportData;
use App\Pipes\Import\NotifyImportComplete;

class ImportService
{
    public function __construct(
        private readonly PipelineService $pipeline
    ) {}
    
    public function import(array $data): array
    {
        return $this->pipeline->process($data, [
            ValidateImportData::class,
            TransformImportData::class,
            SaveImportData::class,
            NotifyImportComplete::class,
        ]);
    }
}
```

### 2. Contact Merge Pipeline
```php
namespace App\Services\Contact;

use App\Services\Pipeline\PipelineService;
use App\Pipes\Contact\ValidateMergeRequest;
use App\Pipes\Contact\DetectDuplicates;
use App\Pipes\Contact\TransferRelationships;
use App\Pipes\Contact\MergeCustomFields;
use App\Pipes\Contact\AuditMerge;

class ContactMergeService
{
    public function __construct(
        private readonly PipelineService $pipeline
    ) {}
    
    public function merge(People $primary, People $duplicate): People
    {
        $context = [
            'primary' => $primary,
            'duplicate' => $duplicate,
        ];
        
        return $this->pipeline->process($context, [
            ValidateMergeRequest::class,
            DetectDuplicates::class,
            TransferRelationships::class,
            MergeCustomFields::class,
            AuditMerge::class,
        ])['primary'];
    }
}
```

### 3. Lead Scoring Pipeline
```php
namespace App\Services\Lead;

use App\Services\Pipeline\PipelineService;
use App\Pipes\Lead\CalculateEngagementScore;
use App\Pipes\Lead\CalculateFitScore;
use App\Pipes\Lead\CalculateRecencyScore;
use App\Pipes\Lead\AssignGrade;

class LeadScoringService
{
    public function __construct(
        private readonly PipelineService $pipeline
    ) {}
    
    public function score(Lead $lead): Lead
    {
        return $this->pipeline->process($lead, [
            CalculateEngagementScore::class,
            CalculateFitScore::class,
            CalculateRecencyScore::class,
            AssignGrade::class,
        ]);
    }
}
```

### 4. Content Moderation Pipeline
```php
namespace App\Services\Content;

use App\Services\Pipeline\PipelineService;
use App\Pipes\Content\CheckProfanity;
use App\Pipes\Content\CheckSpam;
use App\Pipes\Content\CheckSensitiveData;
use App\Pipes\Content\ApplyFilters;

class ContentModerationService
{
    public function __construct(
        private readonly PipelineService $pipeline
    ) {}
    
    public function moderate(string $content): array
    {
        return $this->pipeline->process(['content' => $content], [
            CheckProfanity::class,
            CheckSpam::class,
            CheckSensitiveData::class,
            ApplyFilters::class,
        ]);
    }
}
```

## Creating Pipes

### Basic Pipe Structure
```php
namespace App\Pipes\Import;

use Closure;

class ValidateImportData
{
    public function handle(array $data, Closure $next): mixed
    {
        // Validate data
        if (empty($data)) {
            throw new \InvalidArgumentException('Import data cannot be empty');
        }
        
        // Pass to next pipe
        return $next($data);
    }
}
```

### Pipe with Dependencies
```php
namespace App\Pipes\Contact;

use App\Services\Contact\ContactDuplicateDetectionService;
use Closure;

class DetectDuplicates
{
    public function __construct(
        private readonly ContactDuplicateDetectionService $duplicateDetection
    ) {}
    
    public function handle(array $context, Closure $next): mixed
    {
        $primary = $context['primary'];
        $duplicate = $context['duplicate'];
        
        // Check if they're actually duplicates
        if (!$this->duplicateDetection->areDuplicates($primary, $duplicate)) {
            throw new \InvalidArgumentException('Contacts are not duplicates');
        }
        
        return $next($context);
    }
}
```

### Pipe with Logging
```php
namespace App\Pipes\Lead;

use Closure;
use Illuminate\Support\Facades\Log;

class CalculateEngagementScore
{
    public function handle(Lead $lead, Closure $next): mixed
    {
        Log::info('Calculating engagement score', ['lead_id' => $lead->id]);
        
        // Calculate score based on interactions
        $score = $this->calculateScore($lead);
        $lead->engagement_score = $score;
        
        Log::info('Engagement score calculated', [
            'lead_id' => $lead->id,
            'score' => $score,
        ]);
        
        return $next($lead);
    }
    
    private function calculateScore(Lead $lead): int
    {
        // Implementation
        return 0;
    }
}
```

## Advanced Patterns

### Conditional Pipes
```php
$pipes = [
    ValidateData::class,
    EnrichData::class,
];

if ($requiresApproval) {
    $pipes[] = RequestApproval::class;
}

$result = app(Pipeline::class)
    ->send($data)
    ->through($pipes)
    ->thenReturn();
```

### Pipeline with Via Method
```php
// Custom method name instead of 'handle'
$result = app(Pipeline::class)
    ->send($data)
    ->through([
        ValidateData::class,
        EnrichData::class,
    ])
    ->via('process') // Use 'process' method instead of 'handle'
    ->thenReturn();
```

### Pipeline with Parameters
```php
namespace App\Pipes\Import;

use Closure;

class ValidateImportData
{
    public function handle(array $data, Closure $next, string $type): mixed
    {
        // Use $type parameter for validation
        $rules = $this->getRulesForType($type);
        
        // Validate
        validator($data, $rules)->validate();
        
        return $next($data);
    }
}

// Usage
$result = app(Pipeline::class)
    ->send($data)
    ->through([
        ValidateImportData::class . ':contacts',
        TransformImportData::class,
    ])
    ->thenReturn();
```

### Stoppable Pipelines
```php
namespace App\Pipes\Content;

use Closure;

class CheckProfanity
{
    public function handle(array $context, Closure $next): mixed
    {
        if ($this->hasProfanity($context['content'])) {
            // Stop pipeline and return early
            return [
                'approved' => false,
                'reason' => 'Profanity detected',
            ];
        }
        
        return $next($context);
    }
}
```

## Testing Pipelines

### Unit Test for Pipe
```php
use Tests\TestCase;
use App\Pipes\Import\ValidateImportData;

it('validates import data', function () {
    $pipe = new ValidateImportData();
    $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
    
    $result = $pipe->handle($data, fn ($data) => $data);
    
    expect($result)->toBe($data);
});

it('throws exception for empty data', function () {
    $pipe = new ValidateImportData();
    
    $pipe->handle([], fn ($data) => $data);
})->throws(\InvalidArgumentException::class);
```

### Integration Test for Pipeline
```php
use Tests\TestCase;
use App\Services\Import\ImportService;

it('imports contacts successfully', function () {
    $service = app(ImportService::class);
    
    $data = [
        ['name' => 'John Doe', 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
    ];
    
    $result = $service->import($data);
    
    expect($result['success'])->toBeTrue();
    expect($result['imported'])->toBe(2);
    
    $this->assertDatabaseHas('people', ['email' => 'john@example.com']);
    $this->assertDatabaseHas('people', ['email' => 'jane@example.com']);
});
```

## Filament Integration

### Pipeline Action
```php
use Filament\Actions\Action;
use App\Services\Lead\LeadScoringService;

Action::make('recalculateScore')
    ->label(__('app.actions.recalculate_score'))
    ->icon('heroicon-o-calculator')
    ->action(function (Lead $record) {
        $scoringService = app(LeadScoringService::class);
        $scoringService->score($record);
        
        Notification::make()
            ->title(__('app.notifications.score_recalculated'))
            ->success()
            ->send();
    });
```

### Bulk Pipeline Action
```php
use Filament\Actions\BulkAction;
use App\Services\Lead\LeadScoringService;

BulkAction::make('recalculateScores')
    ->label(__('app.actions.recalculate_scores'))
    ->icon('heroicon-o-calculator')
    ->action(function (Collection $records) {
        $scoringService = app(LeadScoringService::class);
        
        $records->each(fn ($lead) => $scoringService->score($lead));
        
        Notification::make()
            ->title(__('app.notifications.scores_recalculated'))
            ->body(__('app.notifications.scores_recalculated_count', ['count' => $records->count()]))
            ->success()
            ->send();
    })
    ->deselectRecordsAfterCompletion();
```

## Best Practices

### DO:
- ✅ Keep pipes focused on single responsibility
- ✅ Use dependency injection in pipe constructors
- ✅ Log important pipeline steps
- ✅ Handle errors gracefully in each pipe
- ✅ Test pipes in isolation
- ✅ Use descriptive pipe class names
- ✅ Document complex pipeline flows
- ✅ Use type hints for better IDE support

### DON'T:
- ❌ Put too much logic in a single pipe
- ❌ Create circular dependencies between pipes
- ❌ Ignore error handling
- ❌ Skip testing pipeline logic
- ❌ Use pipelines for simple operations
- ❌ Forget to pass data to next pipe
- ❌ Mutate shared state between pipes

## Performance Considerations

### Caching Pipeline Results
```php
public function score(Lead $lead): Lead
{
    $cacheKey = "lead.score.{$lead->id}";
    
    return Cache::remember($cacheKey, 3600, function () use ($lead) {
        return $this->pipeline->process($lead, [
            CalculateEngagementScore::class,
            CalculateFitScore::class,
            CalculateRecencyScore::class,
            AssignGrade::class,
        ]);
    });
}
```

### Queue Heavy Pipelines
```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private readonly array $data
    ) {}
    
    public function handle(ImportService $importService): void
    {
        $importService->import($this->data);
    }
}
```

## Related Documentation
- Laravel Pipelines: https://laravel.com/docs/12.x/helpers#pipeline
- `.kiro/steering/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-conventions.md` - Filament integration patterns

## Quick Reference

### Create a Pipeline Service
1. Create service class with Pipeline dependency
2. Register as singleton in AppServiceProvider
3. Define process methods with pipe arrays

### Create a Pipe
1. Create class in `app/Pipes/{Domain}/`
2. Add `handle(mixed $data, Closure $next)` method
3. Inject dependencies via constructor
4. Process data and call `$next($data)`

### Use in Filament
1. Inject service in action callback
2. Process record(s) through pipeline
3. Show notification with result
4. Handle errors gracefully
