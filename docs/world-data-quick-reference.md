# World Data Quick Reference

## Service Injection

```php
use App\Services\World\WorldDataService;

public function __construct(
    private readonly WorldDataService $worldData
) {}
```

## Common Queries

```php
// Countries
$countries = $worldData->getCountries();
$country = $worldData->getCountry('US', 'iso2');
$popular = $worldData->getPopularCountries();

// States
$states = $worldData->getStates($countryId);
$state = $worldData->getState('CA', 'state_code');

// Cities
$cities = $worldData->getCities($stateId);
$results = $worldData->searchCities('Los Angeles');

// Currencies
$currencies = $worldData->getCurrencies();
$usd = $worldData->getCurrency('USD', 'code');

// Languages
$languages = $worldData->getLanguages();
$english = $worldData->getLanguage('en', 'code');

// Timezones
$timezones = $worldData->getTimezones();
$tz = $worldData->getTimezone('America/New_York', 'name');
```

## Filament Form Pattern

```php
use Filament\Forms\Components\Select;
use Filament\Forms\Get;

// Country
Select::make('country_id')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCountries()->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(fn ($state, $set) => $set('state_id', null))
    ->required(),

// State (dependent)
Select::make('state_id')
    ->options(function (Get $get, WorldDataService $worldData) {
        if (! $countryId = $get('country_id')) return [];
        return $worldData->getStates($countryId)->pluck('name', 'id');
    })
    ->searchable()
    ->preload()
    ->live()
    ->visible(fn (Get $get) => filled($get('country_id')))
    ->required(),

// City (dependent)
Select::make('city_id')
    ->options(function (Get $get, WorldDataService $worldData) {
        if (! $stateId = $get('state_id')) return [];
        return $worldData->getCities($stateId)->pluck('name', 'id');
    })
    ->searchable()
    ->preload()
    ->visible(fn (Get $get) => filled($get('state_id')))
    ->required(),
```

## Model Trait

```php
use App\Models\Concerns\HasWorldAddress;

class Company extends Model
{
    use HasWorldAddress;
}

// Relationships
$company->country; // Country model
$company->state;   // State model
$company->city;    // City model

// Accessors
$company->formatted_address; // Full address string
$company->short_address;     // City, State, Country

// Scopes
Company::inCountry(1)->get();
Company::inState(5)->get();
Company::inCity(100)->get();
```

## Cache Management

```php
// Clear all world data cache
$worldData->clearCache();

// Clear specific cache
Cache::forget('world.country.iso2.US');

// Check cache
Cache::has('world.countries');
```

## Configuration

```php
// config/world.php
'modules' => [
    'states' => true,
    'cities' => true,
    'timezones' => true,
    'currencies' => true,
    'languages' => true,
],

'cache_ttl' => 3600,

'popular_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'JP', 'CN'],
```

## Environment Variables

```env
WORLD_CACHE_TTL=3600
WORLD_DB_CONNECTION=mysql
```

## Testing

```php
use App\Services\World\WorldDataService;

it('retrieves countries', function () {
    $worldData = app(WorldDataService::class);
    $countries = $worldData->getCountries();
    
    expect($countries)->not->toBeEmpty();
});

it('caches country data', function () {
    Cache::flush();
    $worldData = app(WorldDataService::class);
    
    $worldData->getCountry('US', 'iso2');
    expect(Cache::has('world.country.iso2.US'))->toBeTrue();
});
```

## Common Patterns

### Popular Countries Dropdown
```php
Select::make('country_id')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getPopularCountries()->pluck('name', 'id')
    )
```

### Currency Select
```php
Select::make('currency_id')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCurrencies()->pluck('name', 'id')
    )
    ->searchable()
```

### Timezone Select
```php
Select::make('timezone_id')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getTimezones()->pluck('name', 'id')
    )
    ->searchable()
```

## Performance Tips

1. Use ISO codes: `getCountry('US', 'iso2')` (faster)
2. Cache popular data: `getPopularCountries()`
3. Paginate cities: `City::paginate(50)`
4. Eager load: `Country::with('states')->get()`
5. Clear cache after updates: `clearCache()`

## Troubleshooting

```bash
# Reseed data
php artisan world:install

# Clear cache
php artisan cache:clear

# Check config
php artisan config:show world
```

## Documentation

- Full Guide: `docs/world-data-integration.md`
- Steering: `.kiro/steering/world-data-package.md`
- Tests: `tests/Unit/Services/WorldDataServiceTest.php`
