# Laravel Envy - Type-Safe Environment Variables

> **📚 Comprehensive Guide**: See `docs/laravel-envy-integration.md` for complete usage patterns, migration strategies, and examples.

## Core Principles
- Use `App\Support\Env` for all environment variable access instead of `env()` calls
- Provides type safety, validation, and IDE autocompletion
- Centralized configuration with explicit defaults
- All environment variables must be defined in `Env` class

## Basic Usage

### Accessing Environment Variables
```php
use App\Support\Env;

// Type-safe access with defaults
$appName = Env::make()->appName(); // string
$appDebug = Env::make()->appDebug(); // bool
$dbPort = Env::make()->dbPort(); // int

// Nullable values
$githubToken = Env::make()->githubToken(); // ?string
```

### In Configuration Files
```php
// config/app.php
use App\Support\Env;

return [
    'name' => Env::make()->appName(),
    'debug' => Env::make()->appDebug(),
    'url' => Env::make()->appUrl(),
];
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

## Adding New Environment Variables

### Step 1: Add to `.env.example`
```env
NEW_FEATURE_ENABLED=true
NEW_FEATURE_API_KEY=
NEW_FEATURE_TIMEOUT=30
```

### Step 2: Add Method to `Env` Class
```php
// app/Support/Env.php

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

### Step 3: Use in Code
```php
$enabled = Env::make()->newFeatureEnabled();
$apiKey = Env::make()->newFeatureApiKey();
$timeout = Env::make()->newFeatureTimeout();
```

## Type Methods

- `->string()` - String value
- `->int()` - Integer value
- `->float()` - Float value
- `->bool()` - Boolean value
- `->nullable()` - Allow null
- `->default($value)` - Set default
- `->get()` - Retrieve value

## Available Configuration

### Application
- `appName()`, `appEnv()`, `appDebug()`, `appUrl()`, `appTimezone()`, `appLocale()`

### Security
- `securityHeadersEnabled()`, `bcryptRounds()`, `zxcvbnMinScore()`

### Database
- `dbConnection()`, `dbHost()`, `dbPort()`, `dbDatabase()`, `dbUsername()`, `dbPassword()`

### Cache & Session
- `cacheStore()`, `cachePrefix()`, `sessionDriver()`, `sessionLifetime()`

### Redis
- `redisHost()`, `redisPort()`, `redisPassword()`

### Mail
- `mailMailer()`, `mailHost()`, `mailPort()`, `mailFromAddress()`, `mailFromName()`

### OAuth
- `googleClientId()`, `googleClientSecret()`, `githubClientId()`, `githubClientSecret()`, `githubToken()`

### Monitoring
- `sentryDsn()`, `sentryTracesSampleRate()`, `fathomSiteId()`

### OCR
- `ocrDriver()`, `ocrAiEnabled()`, `ocrQueueEnabled()`, `ocrMinConfidence()`, `ocrMaxFileSize()`

### Coverage
- `pcovEnabled()`, `coverageMinPercentage()`, `coverageMinTypeCoverage()`

### Warden
- `wardenScheduleEnabled()`, `wardenCacheEnabled()`, `wardenHistoryEnabled()`

### Unsplash
- `unsplashAccessKey()`, `unsplashCacheEnabled()`, `unsplashAutoDownload()`

### Geo
- `geoAutoTranslate()`, `geoPhoneDefaultCountry()`, `geoCacheTtlMinutes()`

### System Admin
- `sysadminDomain()`, `sysadminPath()`

### Community
- `discordInviteUrl()`

### Email Verification
- `fortifyEmailVerification()`

## Best Practices

### DO:
- ✅ Use `Env::make()` instead of `env()` calls
- ✅ Add all new environment variables to `Env` class
- ✅ Provide sensible defaults
- ✅ Use nullable for truly optional values
- ✅ Group related configuration with section comments
- ✅ Update `.env.example` when adding new variables

### DON'T:
- ❌ Use `env()` directly in application code
- ❌ Skip adding new variables to `Env` class
- ❌ Use magic strings for environment keys
- ❌ Mix `env()` and Envy in same codebase
- ❌ Forget to update documentation

## Testing

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

## Migration from `env()`

### Before
```php
'name' => env('APP_NAME', 'Laravel'),
'debug' => env('APP_DEBUG', false),
```

### After
```php
'name' => Env::make()->appName(),
'debug' => Env::make()->appDebug(),
```

## Integration Points

- ✅ Configuration files (`config/*.php`)
- ✅ Service providers (`app/Providers/*.php`)
- ✅ Service classes (`app/Services/**/*.php`)
- ✅ Filament panel providers
- ✅ Testing (use `putenv()` to set values)

## Related Documentation
- `docs/laravel-envy-integration.md` - Complete integration guide
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `docs/laravel-container-services.md` - Service pattern guidelines

## Quick Commands

```bash
# Find all env() calls to migrate
grep -r "env(" config/

# Clear config cache after changes
php artisan config:clear
```

## Summary

Laravel Envy provides type-safe environment variable access with IDE autocompletion, validation, and explicit defaults. Replace all `env()` calls with `Env::make()->method()` for better developer experience and compile-time safety.
