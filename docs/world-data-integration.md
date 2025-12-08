# World Data Integration (nnjeim/world)

## Overview
The `nnjeim/world` package provides comprehensive global data including countries, states, cities, currencies, languages, and timezones. This integration wraps the package with a service layer following Laravel container patterns and provides Filament v4.3+ resources for management.

## Package Information
- **Package**: `nnjeim/world` v1.1.36
- **Repository**: https://github.com/nnjeim/world
- **License**: MIT
- **Data Coverage**: 250 countries, states, cities, currencies, languages, timezones

## Installation

The package is already installed and configured. To reinstall or update:

```bash
composer require nnjeim/world
php artisan vendor:publish --tag=world
php artisan world:install
```

## Service Architecture

### WorldDataService

Located at `app/Services/World/WorldDataService.php`, this service provides a clean, cached interface to world data.

**Registration**: Singleton in `AppServiceProvider::register()`

```php
$this->app->singleton(WorldDataService::class, function ($app) {
    return new WorldDataService(
        $app->make(GetCountriesAction::class),
        $app->make(GetStatesAction::class),
        $app->make(GetCitiesAction::class),
        $app->make(GetCurrenciesAction::class),
        $app->make(GetLanguagesAction::class),
        $app->make(GetTimezonesAction::class),
        (int) config('world.cache_ttl', 3600)
    );
});
```

### Constructor Injection

```php
use App\Services\World\WorldDataService;

class YourService
{
    public function __construct(
        private readonly WorldDataService $worldData
    ) {}
    
    public function example(): void
    {
        $countries = $this->worldData->getCountries();
        $states = $this->worldData->getStates(countryId: 1);
    }
}
```

## Available Methods

### Countries

```php
// Get all countries
$countries = $worldData->getCountries();

// Get country by ID
$country = $worldData->getCountry(1);

// Get country by ISO2 code
$country = $worldData->getCountry('US', 'iso2');

// Get country by ISO3 code
$country = $worldData->getCountry('USA', 'iso3');

// Search countries
$results = $worldData->searchCountries('United');

// Get popular countries (US, GB, CA, AU, etc.)
$popular = $worldData->getPopularCountries();
```

### States/Provinces

```php
// Get states for a country (by ID)
$states = $worldData->getStates(countryId: 1);

// Get states by country ISO2 code
$states = $worldData->getStates('US', 'iso2');

// Get state by ID
$state = $worldData->getState(1);

// Get state by code
$state = $worldData->getState('CA', 'state_code');
```

### Cities

```php
// Get cities for a state
$cities = $worldData->getCities(stateId: 1);

// Get city by ID
$city = $worldData->getCity(1);

// Get city by name
$city = $worldData->getCity('Los Angeles', 'name');

// Search cities
$results = $worldData->searchCities('Los');

// Search cities in specific state
$results = $worldData->searchCities('Los', stateId: 5);

// Search cities in specific country
$results = $worldData->searchCities('Los', countryId: 1);
```

### Currencies

```php
// Get all currencies
$currencies = $worldData->getCurrencies();

// Get currency by ID
$currency = $worldData->getCurrency(1);

// Get currency by code
$currency = $worldData->getCurrency('USD', 'code');

// Get currencies for a country
$currencies = $worldData->getCountryCurrencies(countryId: 1);
```

### Languages

```php
// Get all languages
$languages = $worldData->getLanguages();

// Get language by ID
$language = $worldData->getLanguage(1);

// Get language by code
$language = $worldData->getLanguage('en', 'code');

// Get languages for a country
$languages = $worldData->getCountryLanguages(countryId: 1);
```

### Timezones

```php
// Get all timezones
$timezones = $worldData->getTimezones();

// Get timezone by ID
$timezone = $worldData->getTimezone(1);

// Get timezone by name
$timezone = $worldData->getTimezone('America/New_York', 'name');

// Get timezones for a country
$timezones = $worldData->getCountryTimezones(countryId: 1);
```

### Cache Management

```php
// Clear all world data cache
$worldData->clearCache();
```

## Filament Integration

### Form Components

#### Country Select

```php
use App\Services\World\WorldDataService;

Select::make('country_id')
    ->label(__('app.labels.country'))
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCountries()->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
    ->live()
    ->required();
```

#### State Select (Dependent on Country)

```php
Select::make('state_id')
    ->label(__('app.labels.state'))
    ->options(function (Get $get, WorldDataService $worldData) {
        $countryId = $get('country_id');
        
        if (! $countryId) {
            return [];
        }
        
        return $worldData->getStates($countryId)->pluck('name', 'id');
    })
    ->searchable()
    ->preload()
    ->live()
    ->required()
    ->visible(fn (Get $get) => filled($get('country_id')));
```

#### City Select (Dependent on State)

```php
Select::make('city_id')
    ->label(__('app.labels.city'))
    ->options(function (Get $get, WorldDataService $worldData) {
        $stateId = $get('state_id');
        
        if (! $stateId) {
            return [];
        }
        
        return $worldData->getCities($stateId)->pluck('name', 'id');
    })
    ->searchable()
    ->preload()
    ->required()
    ->visible(fn (Get $get) => filled($get('state_id')));
```

#### Currency Select

```php
Select::make('currency_id')
    ->label(__('app.labels.currency'))
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCurrencies()->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
    ->required();
```

#### Timezone Select

```php
Select::make('timezone_id')
    ->label(__('app.labels.timezone'))
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getTimezones()->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
    ->required();
```

### Complete Address Form Example

```php
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

Section::make(__('app.labels.address_information'))
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('street_address')
                ->label(__('app.labels.street_address'))
                ->maxLength(255),
            
            TextInput::make('postal_code')
                ->label(__('app.labels.postal_code'))
                ->maxLength(20),
            
            Select::make('country_id')
                ->label(__('app.labels.country'))
                ->options(fn (WorldDataService $worldData) => 
                    $worldData->getCountries()->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('state_id', null);
                    $set('city_id', null);
                })
                ->required(),
            
            Select::make('state_id')
                ->label(__('app.labels.state'))
                ->options(function (Get $get, WorldDataService $worldData) {
                    $countryId = $get('country_id');
                    
                    if (! $countryId) {
                        return [];
                    }
                    
                    return $worldData->getStates($countryId)->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) => $set('city_id', null))
                ->required()
                ->visible(fn (Get $get) => filled($get('country_id'))),
            
            Select::make('city_id')
                ->label(__('app.labels.city'))
                ->options(function (Get $get, WorldDataService $worldData) {
                    $stateId = $get('state_id');
                    
                    if (! $stateId) {
                        return [];
                    }
                    
                    return $worldData->getCities($stateId)->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Get $get) => filled($get('state_id'))),
        ]),
    ]);
```

## Configuration

### Config File: `config/world.php`

```php
return [
    // Allowed countries (empty = all)
    'allowed_countries' => [],
    
    // Disallowed countries
    'disallowed_countries' => [],
    
    // Supported locales
    'accepted_locales' => ['en', 'es', 'fr', 'de', ...],
    
    // Enabled modules
    'modules' => [
        'states' => true,
        'cities' => true,
        'timezones' => true,
        'currencies' => true,
        'languages' => true,
    ],
    
    // Enable API routes
    'routes' => true,
    
    // Database connection
    'connection' => env('WORLD_DB_CONNECTION', env('DB_CONNECTION')),
    
    // Cache TTL (seconds)
    'cache_ttl' => 3600,
    
    // Popular countries for quick access
    'popular_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'JP', 'CN'],
];
```

### Environment Variables

```env
# Use separate database for world data (optional)
WORLD_DB_CONNECTION=mysql

# Cache TTL in seconds (default: 3600)
WORLD_CACHE_TTL=3600
```

## Database Tables

The package creates the following tables:

- `countries` - Country data with ISO codes, phone codes, regions
- `states` - State/province data linked to countries
- `cities` - City data linked to states
- `currencies` - Currency data with codes and symbols
- `languages` - Language data with codes
- `timezones` - Timezone data with GMT offsets

## Performance Considerations

### Caching

All service methods use Laravel cache with configurable TTL (default 1 hour):

```php
// Cache keys follow pattern: world.{entity}.{column}.{identifier}
'world.country.iso2.US'
'world.states.id.1'
'world.cities.id.5'
```

### Cache Warming

Warm cache for frequently accessed data:

```php
use App\Services\World\WorldDataService;

$worldData = app(WorldDataService::class);

// Warm popular countries
$worldData->getPopularCountries();

// Warm specific country data
$country = $worldData->getCountry('US', 'iso2');
$worldData->getStates($country->id);
$worldData->getCountryCurrencies($country->id);
```

### Eager Loading

When displaying related data, eager load relationships:

```php
use Nnjeim\World\Models\Country;

// Load countries with states
$countries = Country::with('states')->get();

// Load countries with currencies and timezones
$countries = Country::with(['currencies', 'timezones'])->get();
```

### Pagination

For large datasets (especially cities), use pagination:

```php
use Nnjeim\World\Models\City;

$cities = City::where('state_id', $stateId)
    ->orderBy('name')
    ->paginate(50);
```

## Testing

### Unit Tests

```php
use App\Services\World\WorldDataService;
use Tests\TestCase;

it('retrieves countries', function () {
    $worldData = app(WorldDataService::class);
    $countries = $worldData->getCountries();
    
    expect($countries)->toBeInstanceOf(Collection::class);
    expect($countries)->not->toBeEmpty();
});

it('retrieves country by ISO2 code', function () {
    $worldData = app(WorldDataService::class);
    $country = $worldData->getCountry('US', 'iso2');
    
    expect($country)->not->toBeNull();
    expect($country->name)->toBe('United States');
    expect($country->iso2)->toBe('US');
});

it('retrieves states for country', function () {
    $worldData = app(WorldDataService::class);
    $country = $worldData->getCountry('US', 'iso2');
    $states = $worldData->getStates($country->id);
    
    expect($states)->toBeInstanceOf(Collection::class);
    expect($states)->not->toBeEmpty();
    expect($states->first()->country_id)->toBe($country->id);
});
```

### Feature Tests

```php
use App\Services\World\WorldDataService;
use Illuminate\Support\Facades\Cache;

it('caches country data', function () {
    Cache::flush();
    
    $worldData = app(WorldDataService::class);
    
    // First call - hits database
    $country1 = $worldData->getCountry('US', 'iso2');
    
    // Second call - hits cache
    $country2 = $worldData->getCountry('US', 'iso2');
    
    expect($country1->id)->toBe($country2->id);
    expect(Cache::has('world.country.iso2.US'))->toBeTrue();
});

it('clears world data cache', function () {
    $worldData = app(WorldDataService::class);
    
    // Populate cache
    $worldData->getCountries();
    expect(Cache::has('world.countries'))->toBeTrue();
    
    // Clear cache
    $worldData->clearCache();
    expect(Cache::has('world.countries'))->toBeFalse();
});
```

## API Routes

The package provides REST API endpoints (if `routes` is enabled in config):

```
GET /api/world/countries
GET /api/world/countries/{id}
GET /api/world/countries/{id}/states
GET /api/world/countries/{id}/cities
GET /api/world/states
GET /api/world/states/{id}
GET /api/world/states/{id}/cities
GET /api/world/cities
GET /api/world/cities/{id}
GET /api/world/currencies
GET /api/world/timezones
GET /api/world/languages
```

## Best Practices

### DO:
- ✅ Use `WorldDataService` for all world data access
- ✅ Cache frequently accessed data
- ✅ Use dependent selects for country → state → city
- ✅ Eager load relationships when displaying related data
- ✅ Use ISO codes for country lookups (faster than names)
- ✅ Paginate large city datasets
- ✅ Clear cache after data updates
- ✅ Use popular countries list for common selections

### DON'T:
- ❌ Query world models directly in controllers/resources
- ❌ Skip caching for repeated queries
- ❌ Load all cities without filtering by state
- ❌ Forget to clear dependent fields when parent changes
- ❌ Use string matching for country lookups (use ISO codes)
- ❌ Ignore cache warming for frequently accessed data
- ❌ Hardcode country/currency lists

## Troubleshooting

### Cities Not Loading

Ensure cities module is enabled in config:

```php
'modules' => [
    'cities' => true,
],
```

### Cache Issues

Clear world data cache:

```php
php artisan cache:clear
// or
app(WorldDataService::class)->clearCache();
```

### Missing Data

Reseed world data:

```php
php artisan world:install
```

### Performance Issues

1. Enable caching in `.env`:
```env
CACHE_DRIVER=redis
```

2. Increase cache TTL:
```env
WORLD_CACHE_TTL=7200
```

3. Use separate database for world data:
```env
WORLD_DB_CONNECTION=world_db
```

## Related Documentation

- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `.kiro/steering/filament-forms-inputs.md` - Filament form patterns
- Package docs: https://github.com/nnjeim/world

## Migration Guide

### From Manual Country Lists

**Before:**
```php
Select::make('country')
    ->options([
        'US' => 'United States',
        'GB' => 'United Kingdom',
        // ...
    ]);
```

**After:**
```php
Select::make('country_id')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCountries()->pluck('name', 'id')
    )
    ->searchable()
    ->preload();
```

### From String Fields to Relationships

**Before:**
```php
TextInput::make('country')->maxLength(255);
TextInput::make('state')->maxLength(255);
TextInput::make('city')->maxLength(255);
```

**After:**
```php
Select::make('country_id')->relationship('country', 'name');
Select::make('state_id')->relationship('state', 'name');
Select::make('city_id')->relationship('city', 'name');
```

## Support

For package issues, see: https://github.com/nnjeim/world/issues
For integration issues, check: `docs/laravel-container-services.md`
