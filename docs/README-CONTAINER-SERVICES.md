# Laravel Container Services - Quick Reference

## 📚 Documentation Index

### Core Documentation
1. **[Laravel Container Services](laravel-container-services.md)** - Comprehensive guide covering all aspects of the container service pattern
2. **[Implementation Guide](laravel-container-implementation-guide.md)** - Step-by-step practical guide with real-world examples
3. **[Integration Summary](CONTAINER_SERVICES_INTEGRATION.md)** - Overview of what was integrated and how to use it

### Steering Rules
- **[Container Services Steering](.kiro/steering/laravel-container-services.md)** - Concise guidelines for daily development

### Example Code
- **Service**: `app/Services/Examples/ExampleEmailVerificationService.php`
- **Tests**: `tests/Unit/Services/Examples/ExampleEmailVerificationServiceTest.php`

## 🚀 Quick Start

### 1. Create a Service

```php
<?php

namespace App\Services;

class YourService
{
    public function __construct(
        private readonly DependencyService $dependency,
        private readonly string $configValue
    ) {}
    
    public function doSomething(): Result
    {
        // Your business logic here
    }
}
```

### 2. Register in AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(YourService::class, function ($app) {
        return new YourService(
            dependency: $app->make(DependencyService::class),
            configValue: config('your.config.value')
        );
    });
}
```

### 3. Use in Filament Resources

```php
Action::make('doSomething')
    ->action(function () {
        $service = app(YourService::class);
        $result = $service->doSomething();
        
        Notification::make()
            ->title('Success!')
            ->success()
            ->send();
    })
```

### 4. Test Your Service

```php
it('does something correctly', function () {
    $dependency = Mockery::mock(DependencyService::class);
    $service = new YourService($dependency, 'test-value');
    
    $result = $service->doSomething();
    
    expect($result)->toBeInstanceOf(Result::class);
});
```

## 📖 Common Patterns

### Pattern 1: Service with External API

```php
class ApiService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 30
    ) {}
    
    public function fetchData(): array
    {
        $response = Http::external()
            ->timeout($this->timeout)
            ->withToken($this->apiKey)
            ->get('/endpoint');
            
        return $response->json();
    }
}
```

### Pattern 2: Service with Caching

```php
class CachedService
{
    public function __construct(
        private readonly int $cacheTtl = 3600
    ) {}
    
    public function getData(int $id): Model
    {
        return Cache::remember(
            "data.{$id}",
            $this->cacheTtl,
            fn () => Model::find($id)
        );
    }
}
```

### Pattern 3: Service with Repository

```php
class DomainService
{
    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly AuditLogService $auditLog
    ) {}
    
    public function create(array $data): Model
    {
        $model = $this->repository->create($data);
        $this->auditLog->log('created', $model);
        return $model;
    }
}
```

## ✅ Best Practices Checklist

When creating a service, ensure:

- [ ] Uses constructor injection with readonly properties
- [ ] Registered in `AppServiceProvider::register()`
- [ ] Has single, clear responsibility
- [ ] Dependencies are type-hinted
- [ ] Error handling with try-catch and logging
- [ ] Returns typed results (DTOs, models, arrays)
- [ ] Has unit tests with mocked dependencies
- [ ] Has feature tests with real dependencies
- [ ] Documentation in docblock
- [ ] Configuration from config files
- [ ] No service locator pattern (`app()` in business logic)
- [ ] Follows naming conventions (ends with `Service`)

## 🎯 Service Types

### Action Services
Handle specific business actions (create, update, merge, convert)
- `ContactMergeService`
- `LeadConversionService`
- `InvoiceGenerationService`

### Query Services
Handle complex queries and data retrieval
- `OpportunityMetricsService`
- `CustomerProfileService`
- `ReportGenerationService`

### Integration Services
Integrate with external systems
- `GitHubService`
- `CalendarSyncService`
- `PaymentProcessorService`

### Repository Services
Abstract data access layer
- `EloquentCompanyRepository`
- `CachedUserRepository`
- `ApiDataRepository`

## 🧪 Testing Patterns

### Unit Test (Mocked Dependencies)
```php
it('performs action correctly', function () {
    $mock = Mockery::mock(DependencyService::class);
    $mock->shouldReceive('method')->once()->andReturn('result');
    
    $service = new YourService($mock);
    $result = $service->performAction();
    
    expect($result)->toBe('expected');
});
```

### Feature Test (Real Dependencies)
```php
it('integrates with database correctly', function () {
    $model = Model::factory()->create();
    $service = app(YourService::class);
    
    $result = $service->process($model);
    
    expect($result)->toBeInstanceOf(Model::class);
    $this->assertDatabaseHas('table', ['id' => $result->id]);
});
```

### Integration Test (HTTP Fakes)
```php
it('calls external API correctly', function () {
    Http::fake(['api.example.com/*' => Http::response(['data' => 'value'], 200)]);
    
    $service = app(ApiService::class);
    $result = $service->fetchData();
    
    expect($result)->toHaveKey('data');
    Http::assertSentCount(1);
});
```

## 🔧 Troubleshooting

### Service Not Found
**Error**: `Target class [App\Services\YourService] does not exist.`

**Solution**: Register service in `AppServiceProvider::register()`:
```php
$this->app->singleton(YourService::class);
```

### Circular Dependency
**Error**: Services depend on each other in a loop.

**Solution**: Extract shared logic to a third service or use events to decouple.

### Cannot Mock Service
**Error**: Service is instantiated directly instead of resolved from container.

**Solution**: Always use constructor injection or `app()` helper, never `new Service()`.

## 📦 Integration with Existing Patterns

The container service pattern works with:

- ✅ **HTTP Client Macros** - Use `Http::external()` in services
- ✅ **Array Helpers** - Use `ArrayHelper` for data formatting
- ✅ **Date Scopes** - Services leverage model date scopes
- ✅ **Filament Actions** - Services power resource actions
- ✅ **Queue Jobs** - Services called from queued jobs
- ✅ **Precognition** - Services validate form data

## 📚 Further Reading

### Documentation
- [Laravel Container Services](laravel-container-services.md) - Full documentation
- [Implementation Guide](laravel-container-implementation-guide.md) - Practical examples
- [Integration Summary](CONTAINER_SERVICES_INTEGRATION.md) - What was integrated

### Related Patterns
- [HTTP Client](laravel-http-client.md) - HTTP client macros
- [Array Helpers](array-helpers.md) - Array formatting utilities
- [Testing Infrastructure](testing-infrastructure.md) - Testing patterns
- [Pest Laravel Expectations](pest-laravel-expectations.md) - Testing assertions

### External Resources
- [Laravel Container Documentation](https://laravel.com/docs/container)
- [Dependency Injection in Laravel](https://laravel.com/docs/providers)
- [Testing Laravel Services](https://laravel.com/docs/testing)
- [Filament Actions](https://filamentphp.com/docs/actions)

## 🎓 Learning Path

1. **Start Here**: Read [Implementation Guide](laravel-container-implementation-guide.md)
2. **Study Example**: Review `app/Services/Examples/ExampleEmailVerificationService.php`
3. **Practice**: Create a simple service following the patterns
4. **Test**: Write unit and feature tests for your service
5. **Integrate**: Use your service in a Filament resource
6. **Refine**: Review [Best Practices](.kiro/steering/laravel-container-services.md)

## 💡 Tips

- Start with simple services and gradually add complexity
- Always write tests before integrating with Filament
- Use DTOs for complex return values
- Log errors with context for debugging
- Cache expensive operations with appropriate TTL
- Use transactions for multi-step operations
- Keep services focused on single responsibility
- Prefer composition over inheritance

## 🤝 Contributing

When adding new services:
1. Follow the documented patterns
2. Register in `AppServiceProvider`
3. Write comprehensive tests
4. Document in service docblock
5. Update this README if introducing new patterns

---

**Last Updated**: December 8, 2025  
**Laravel Version**: 12.x  
**Filament Version**: 4.3+  
**PHP Version**: 8.4+
