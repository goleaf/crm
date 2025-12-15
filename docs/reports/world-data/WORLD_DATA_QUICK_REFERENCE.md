# World Data Service - Quick Reference

## Service Access

```php
use App\Services\World\WorldDataService;

// Constructor injection (preferred)
public function __construct(
    private readonly WorldDataService $worldData
) {}

// Or resolve from container
$worldData = app(WorldDataService::class);
```

## Regional Filtering

```php
// Get countries by region
$europeanCountries = $worldData->getCountriesByRegion('Europe');
$asianCountries = $worldData->getCountriesByRegion('Asia');

// Get countries by subregion
$seAsian = $worldData->getCountriesBySubregion('South-Eastern Asia');

// Get all regions
$regions = $worldData->getRegions();
// Returns: ['Europe', 'Asia', 'Americas', 'Africa', 'Oceania']

// Get EU member states
$euCountries = $worldData->getEUCountries();
// Returns: AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, LV, LT, LU, MT, NL, PL, PT, RO, SK, SI, ES, SE
```

## Enhanced Lookups

```php
// Find countries by phone code
$countries = $worldData->getCountriesByPhoneCode('+1');
// Returns: US, CA, and other +1 countries

// Get country with full details (eager loaded)
$country = $worldData->getCountryWithDetails('US', 'iso2');
// Includes: currencies, languages, timezones
```

## Address Utilities

```php
// Format address for display
$formatted = $worldData->formatAddress(
    street: '123 Main St',
    city: 'New York',
    state: 'NY',
    postalCode: '10001',
    country: 'United States'
);
// Returns: "123 Main St, New York, NY, 10001, United States"

// Get country flag emoji
$flag = $worldData->getCountryFlag('US'); // 🇺🇸
$flag = $worldData->getCountryFlag('GB'); // 🇬🇧
$flag = $worldData->getCountryFlag('FR'); // 🇫🇷
```

## Postal Code Validation

```php
// Validate postal codes (50+ countries supported)
$valid = $worldData->validatePostalCode('10001', 'US'); // true
$valid = $worldData->validatePostalCode('SW1A 1AA', 'GB'); // true
$valid = $worldData->validatePostalCode('K1A 0B1', 'CA'); // true
$valid = $worldData->validatePostalCode('ABC', 'US'); // false

// Supported countries:
// US, GB, CA, AU, DE, FR, IT, ES, NL, BE, CH, AT, SE, NO, DK, FI, PL, CZ, PT, IE,
// JP, CN, IN, BR, MX, AR, ZA, NZ, SG, MY, TH, PH, ID, VN, KR, TR, RU, UA, GR, RO,
// HU, SK, SI, HR, BG, LT, LV, EE, CY, MT, LU, IS
```

## Distance Calculation

```php
// Calculate distance between cities (in kilometers)
$distance = $worldData->getDistanceBetweenCities($cityId1, $cityId2);
// Returns: float (distance in km) or null if coordinates unavailable
```

## Filament Examples

### Regional Country Select
```php
Select::make('country_id')
    ->label(__('app.labels.country'))
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getCountriesByRegion('Europe')->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
```

### Country with Flag Display
```php
TextColumn::make('country.name')
    ->label(__('app.labels.country'))
    ->formatStateUsing(function ($record, WorldDataService $worldData) {
        if (!$record->country) {
            return '—';
        }
        
        $flag = $worldData->getCountryFlag($record->country->iso2);
        return "{$flag} {$record->country->name}";
    })
    ->searchable()
    ->sortable()
```

### Postal Code Validation
```php
TextInput::make('postal_code')
    ->label(__('app.labels.postal_code'))
    ->rules([
        fn (Get $get, WorldDataService $worldData): \Closure => 
            function (string $attribute, $value, \Closure $fail) use ($get, $worldData) {
                $countryId = $get('country_id');
                if (!$countryId) {
                    return;
                }
                
                $country = $worldData->getCountry($countryId);
                if (!$country) {
                    return;
                }
                
                if (!$worldData->validatePostalCode($value, $country->iso2)) {
                    $fail(__('validation.postal_code_invalid', ['country' => $country->name]));
                }
            },
    ])
```

### EU Countries Filter
```php
SelectFilter::make('country_id')
    ->label('EU Countries')
    ->options(fn (WorldDataService $worldData) => 
        $worldData->getEUCountries()->pluck('name', 'id')
    )
```

### Formatted Address Display
```php
TextEntry::make('address')
    ->label(__('app.labels.address'))
    ->formatStateUsing(function ($record, WorldDataService $worldData) {
        return $worldData->formatAddress(
            street: $record->street,
            city: $record->city?->name,
            state: $record->state?->name,
            postalCode: $record->postal_code,
            country: $record->country?->name
        );
    })
```

## Caching

All methods use caching with 1-hour TTL by default:

```php
// Clear cache when needed
$worldData->clearCache();

// Cache keys follow pattern:
// world.{entity}.{column}.{identifier}
// Examples:
// - world.countries
// - world.country.iso2.US
// - world.states.id.123
// - world.popular_countries
```

## Performance Tips

1. **Use ISO codes for lookups** (faster than name matching):
   ```php
   $country = $worldData->getCountry('US', 'iso2');
   ```

2. **Eager load relationships** when displaying related data:
   ```php
   $country = $worldData->getCountryWithDetails('US', 'iso2');
   ```

3. **Cache frequently accessed data** in your own layer if needed

4. **Use popular countries** for common selections:
   ```php
   $popular = $worldData->getPopularCountries();
   ```

## Related Documentation

- **Complete Guide**: `docs/world-data-enhanced-features.md`
- **Steering File**: `.kiro/steering/world-data-package.md`
- **Original Integration**: `docs/world-data-integration.md`
- **Service Patterns**: `docs/laravel-container-services.md`
- **Filament Forms**: `.kiro/steering/filament-forms-inputs.md`

## Package Information

- **Package**: `nnjeim/world` v1.1.36
- **Service**: `App\Services\World\WorldDataService`
- **Models**: `Nnjeim\World\Models\{Country, State, City, Currency, Language, Timezone}`
- **Cache TTL**: 3600 seconds (1 hour)
- **Cache Driver**: Configured in `config/cache.php`
