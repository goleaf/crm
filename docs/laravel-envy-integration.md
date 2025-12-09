# Laravel Envy Integration

> **📦 Package**: `worksome/envy` v1.4.0  
> **📚 Laravel News**: https://laravel-news.com/laravel-envy  
> **🔗 GitHub**: https://github.com/worksome/envy

## Overview

Laravel Envy provides a type-safe, fluent API for accessing environment variables with validation, default values, and IDE autocompletion. This eliminates the need for manual `env()` calls and provides compile-time safety for environment configuration.

## Core Benefits

### ✅ Type Safety
- Compile-time type checking for environment variables
- Prevents runtime errors from missing or invalid env values
- IDE autocompletion for all environment variables

### ✅ Validation
- Built-in validation for required variables
- Type coercion (string, int, float, bool)
- Nullable support for optional variables

### ✅ Default Values
- Explicit default values for all variables
- No more scattered `env('KEY', 'default')` calls
- Centralized configuration

### ✅ Developer Experience
- Single source of truth for all environment variables
- Self-documenting code
- Easy to discover available configuration

## Installation

Already installed via Composer:

```bash
composer require worksome/envy --dev
```

## Basic Usage

### Accessing Environment Variables

```php
use App\Support\Env;

// Type-safe access with defaults
$appName = Env::make()->appName(); // string
$appDebug = Env::make()->appDebug(); // bool
$dbPort = Env::make()->dbPort(); // int
$sentryRate = Env::make()->sentryTracesSampleRate(); // float

// Nullable values
$githubToken = Env::make()->githubToken(); // ?string
```

### In Configuration Files

Replace `env()` calls with Envy:

```php
// Before
'name' => env('APP_NAME', 'Laravel'),
'debug' => env('APP_DEBUG', false),

// After
'name' => Env::make()->appName(),
'debug' => Env::make()->appDebug(),
```

### In Service Providers

```php
use App\Support\Env;

public function register(): void
{
    $this->app->singleton(GitHubService::class, function ($app) {
        return new GitHubService(
            token: Env::make()->githubToken(),
            cacheTtl: 3600
        );
    });
}
```

### In Controllers & Services

```php
use App\Support\Env;

class MailService
{
    public function __construct(
        private readonly string $fromAddress,
        private readonly string $fromName
    ) {}
    
    public static function fromEnv(): self
    {
        $env = Env::make();
        
        return new self(
            fromAddress: $env->mailFromAddress(),
            fromName: $env->mailFromName()
        );
    }
}
```

## Available Methods

### Application Configuration

```php
$env = Env::make();

$env->appName();              // string - Application name
$env->appEnv();               // string - Environment (local, production)
$env->appDebug();             // bool - Debug mode
$env->appUrl();               // string - Application URL
$env->appTimezone();          // string - Timezone
$env->appLocale();            // string - Default locale
$env->appFallbackLocale();    // string - Fallback locale
```

### Security Configuration

```php
$env->securityHeadersEnabled();      // bool
$env->securityHeadersOnlyHttps();    // bool
$env->bcryptRounds();                // int
$env->zxcvbnMinScore();              // int
```

### Database Configuration

```php
$env->dbConnection();    // string
$env->dbHost();          // string
$env->dbPort();          // int
$env->dbDatabase();      // string
$env->dbUsername();      // string
$env->dbPassword();      // string
```

### Cache & Session

```php
$env->cacheStore();        // string
$env->cachePrefix();       // string
$env->sessionDriver();     // string
$env->sessionLifetime();   // int
```

### Redis Configuration

```php
$env->redisHost();       // string
$env->redisPort();       // int
$env->redisPassword();   // ?string (nullable)
```

### Mail Configuration

```php
$env->mailMailer();        // string
$env->mailHost();          // string
$env->mailPort();          // int
$env->mailFromAddress();   // string
$env->mailFromName();      // string
```

### OAuth Configuration

```php
$env->googleClientId();       // ?string
$env->googleClientSecret();   // ?string
$env->githubClientId();       // ?string
$env->githubClientSecret();   // ?string
$env->githubToken();          // ?string
```

### Monitoring & Analytics

```php
$env->sentryDsn();                  // ?string
$env->sentryTracesSampleRate();     // float
$env->fathomSiteId();               // ?string
```

### OCR Configuration

```php
$env->ocrDriver();            // string
$env->ocrTesseractPath();     // string
$env->ocrAiEnabled();         // bool
$env->ocrQueueEnabled();      // bool
$env->ocrMinConfidence();     // float
$env->ocrMaxFileSize();       // int
```

### Code Coverage Configuration

```php
$env->pcovEnabled();                // bool
$env->coverageMinPercentage();      // int
$env->coverageMinTypeCoverage();    // float
```

### Warden Security Audit

```php
$env->wardenScheduleEnabled();      // bool
$env->wardenScheduleFrequency();    // string
$env->wardenCacheEnabled();         // bool
$env->wardenCacheDuration();        // int
$env->wardenHistoryEnabled();       // bool
```

### Unsplash Configuration

```php
$env->unsplashAccessKey();      // ?string
$env->unsplashSecretKey();      // ?string
$env->unsplashCacheEnabled();   // bool
$env->unsplashCacheTtl();       // int
$env->unsplashAutoDownload();   // bool
```

### Geo Configuration

```php
$env->geoAutoTranslate();           // bool
$env->geoPhoneDefaultCountry();     // string
$env->geoCacheTtlMinutes();         // int
```

### System Admin Configuration

```php
$env->sysadminDomain();    // ?string
$env->sysadminPath();      // string
```

### Community Links

```php
$env->discordInviteUrl();    // string
```

### Email Verification

```php
$env->fortifyEmailVerification();    // bool
```

## Adding New Environment Variables

### Step 1: Add to `.env.example`

```env
# New Feature Configuration
NEW_FEATURE_ENABLED=true
NEW_FEATURE_API_KEY=
NEW_FEATURE_TIMEOUT=30
```

### Step 2: Add Method to `Env` Class

```php
// app/Support/Env.php

// =========================================================================
// New Feature Configuration
// =========================================================================

public function newFeatureEnabled(): bool
{
    return $this->bool('NEW_FEATURE_ENABLED')->default(true)->get();
}

public function newFeatureApiKey(): ?string
{
    return $this->string('NEW_FEATURE_API_KEY')->nullable()->get();
}

public function newFeatureTimeout(): int
{
    return $this->int('NEW_FEATURE_TIMEOUT')->default(30)->get();
}
```

### Step 3: Use in Configuration

```php
// config/new-feature.php

use App\Support\Env;

return [
    'enabled' => Env::make()->newFeatureEnabled(),
    'api_key' => Env::make()->newFeatureApiKey(),
    'timeout' => Env::make()->newFeatureTimeout(),
];
```

## Type Coercion

Envy automatically handles type coercion:

```php
// String
$this->string('KEY')->default('value')->get();

// Integer
$this->int('KEY')->default(42)->get();

// Float
$this->float('KEY')->default(3.14)->get();

// Boolean
$this->bool('KEY')->default(true)->get();

// Nullable
$this->string('KEY')->nullable()->get(); // ?string
```

## Validation

### Required Variables

```php
// Throws exception if not set
$this->string('REQUIRED_KEY')->get();
```

### Optional Variables with Defaults

```php
// Returns default if not set
$this->string('OPTIONAL_KEY')->default('fallback')->get();
```

### Nullable Variables

```php
// Returns null if not set
$this->string('NULLABLE_KEY')->nullable()->get();
```

## Migration Strategy

### Step 1: Identify `env()` Calls

```bash
# Find all env() calls
grep -r "env(" config/
```

### Step 2: Add Methods to Env Class

For each unique `env()` call, add a corresponding method to `App\Support\Env`.

### Step 3: Replace in Configuration Files

```php
// Before
'key' => env('MY_KEY', 'default'),

// After
'key' => Env::make()->myKey(),
```

### Step 4: Update Service Providers

Replace `env()` calls in service providers with Envy methods.

### Step 5: Update Tests

```php
// Before
config(['app.name' => 'Test App']);

// After
// Envy reads from env(), so set env in tests
putenv('APP_NAME=Test App');
```

## Best Practices

### DO:
- ✅ Add all environment variables to `Env` class
- ✅ Use descriptive method names (camelCase)
- ✅ Provide sensible defaults
- ✅ Group related configuration together
- ✅ Document complex configuration with comments
- ✅ Use nullable for truly optional values
- ✅ Keep `Env` class organized with section comments

### DON'T:
- ❌ Use `env()` directly in application code
- ❌ Skip adding new variables to `Env` class
- ❌ Use magic strings for environment keys
- ❌ Forget to update `.env.example`
- ❌ Mix `env()` and Envy in same codebase
- ❌ Skip type hints on Env methods

## Testing

### Unit Tests

```php
use App\Support\Env;

it('returns correct app name', function () {
    putenv('APP_NAME=Test App');
    
    expect(Env::make()->appName())->toBe('Test App');
});

it('uses default when env not set', function () {
    putenv('APP_NAME=');
    
    expect(Env::make()->appName())->toBe('Relaticle');
});
```

### Feature Tests

```php
it('configures service with env values', function () {
    putenv('GITHUB_TOKEN=test-token');
    
    $service = app(GitHubService::class);
    
    expect($service->getToken())->toBe('test-token');
});
```

## IDE Autocompletion

Envy provides full IDE autocompletion:

```php
$env = Env::make();
$env->app // IDE suggests: appName(), appEnv(), appDebug(), etc.
```

## Performance

- Envy is a thin wrapper around `env()`
- No performance overhead
- Values are not cached (respects Laravel's env caching)
- Use `php artisan config:cache` for production

## Integration with Existing Patterns

### Works With
- ✅ Service Container Pattern (inject Env in services)
- ✅ Configuration Files (replace `env()` calls)
- ✅ Service Providers (use in `register()` and `boot()`)
- ✅ Filament Configuration (use in panel providers)
- ✅ Testing (set env vars with `putenv()`)

### Example: Complete Service Setup

```php
// app/Services/Payment/PaymentService.php
use App\Support\Env;

class PaymentService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout,
        private readonly bool $sandbox
    ) {}
    
    public static function fromEnv(): self
    {
        $env = Env::make();
        
        return new self(
            apiKey: $env->paymentApiKey(),
            timeout: $env->paymentTimeout(),
            sandbox: $env->paymentSandbox()
        );
    }
}

// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(PaymentService::class, fn () => 
        PaymentService::fromEnv()
    );
}
```

## Troubleshooting

### Method Not Found

**Problem**: `Call to undefined method App\Support\Env::myMethod()`

**Solution**: Add the method to `App\Support\Env` class.

### Type Mismatch

**Problem**: Expected `int`, got `string`

**Solution**: Use correct type method (`->int()` instead of `->string()`).

### Null Value

**Problem**: Getting `null` when expecting a value

**Solution**: Either provide a default or make the method nullable:
```php
// With default
$this->string('KEY')->default('fallback')->get();

// Nullable
$this->string('KEY')->nullable()->get();
```

## Related Documentation

- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `docs/laravel-container-services.md` - Service pattern guidelines
- `docs/testing-infrastructure.md` - Testing patterns

## Quick Reference

### Common Patterns

```php
use App\Support\Env;

// Get single value
$debug = Env::make()->appDebug();

// Use in config
'debug' => Env::make()->appDebug(),

// Use in service
public static function fromEnv(): self
{
    $env = Env::make();
    return new self($env->apiKey());
}

// Add new variable
public function myNewVar(): string
{
    return $this->string('MY_NEW_VAR')->default('default')->get();
}
```

### Type Methods

- `->string()` - String value
- `->int()` - Integer value
- `->float()` - Float value
- `->bool()` - Boolean value
- `->nullable()` - Allow null
- `->default($value)` - Set default
- `->get()` - Retrieve value

## Summary

Laravel Envy provides type-safe environment variable access with:
- IDE autocompletion
- Compile-time type checking
- Centralized configuration
- Explicit defaults
- Validation support

Replace all `env()` calls with Envy methods for better developer experience and fewer runtime errors.
