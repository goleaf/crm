# Enhanced World Data Features

## Overview
The `WorldDataService` has been enhanced with additional features for CRM use cases including regional filtering, postal code validation, distance calculations, and address formatting.

## New Features

### Regional Filtering

Filter countries by geographic region or subregion:

```php
use App\Services\World\WorldDataService;

public function __construct(
    private readonly WorldDataService $worldData
) {}

// Get all European countries
$europeanCountries = $this->worldData->getCountriesByRegion('Europe');

// Get all Southeast Asian countries
$seAsianCountries = $this->worldData->getCountriesBySubregion('South-Eastern Asia');

// Get list of all regions
$regions = $this->worldData->getRegions();
```

### EU Countries

Quick access to European Union member states:

```php
$euCountries = $this->worldData->getEUCountries();
// Returns: AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, LV, LT, LU, MT, NL, PL, PT, RO, SK, SI, ES, SE
```

### Phone Code Lookup

Find countries by international dialing code:

```php
$countries = $this->worldData->getCountriesByPhoneCode('+1');
// Returns: US, CA (and other +1 countries)
```

### Full Country Details

Load country with all related data in one call:

```php
$country = $this->worldData->getCountryWithDetails('US', 'iso2');
// Includes: currencies, languages, timezones (eager loaded)
```


### Address Formatting

Format addresses for display:

```php
$formatted = $this->worldData->formatAddress(
    street: '123 Main St',
    city: 'New York',
    state: 'NY',
    postalCode: '10001',
    country: 'United States'
);
// Returns: "123 Main St, New York, NY, 10001, United States"
```

### Country Flags

Get emoji flags from ISO2 codes:

```php
$flag = $this->worldData->getCountryFlag('US'); // 🇺🇸
$flag = $this->worldData->getCountryFlag('GB'); // 🇬🇧
$flag = $this->worldData->getCountryFlag('FR'); // 🇫🇷
```

### Postal Code Validation

Validate postal codes for 50+ countries:

```php
// US ZIP code
$valid = $this->worldData->validatePostalCode('10001', 'US'); // true
$valid = $this->worldData->validatePostalCode('ABC123', 'US'); // false

// UK postcode
$valid = $this->worldData->validatePostalCode('SW1A 1AA', 'GB'); // true

// Canadian postal code
$valid = $this->worldData->validatePostalCode('K1A 0B1', 'CA'); // true
```

Supported countries: US, GB, CA, AU, DE, FR, IT, ES, NL, BE, CH, AT, SE, NO, DK, FI, PL, CZ, PT, IE, JP, CN, IN, BR, MX, AR, ZA, NZ, SG, MY, TH, PH, ID, VN, KR, TR, RU, UA, GR, RO, HU, SK, SI, HR, BG, LT, LV, EE, CY, MT, LU, IS

### Distance Calculation

Calculate distance between cities using Haversine formula:

```php
$distance = $this->worldData->getDistanceBetweenCities($cityId1, $cityId2);
// Returns distance in kilometers (float) or null if coordinates unavailable
```


## Filament Integration Examples

### Regional Country Select

```php
use Filament\Forms\Components\Select;
use App\Services\World\WorldDataService;

Select::make('country_id')
    ->label(__('app.labels.country'))
    ->options(function (WorldDataService $worldData) {
        return $worldData->getCountriesByRegion('Europe')
            ->pluck('name', 'id');
    })
    ->searchable()
    ->preload()
```

### Country with Flag Display

```php
use Filament\Tables\Columns\TextColumn;
use App\Services\World\WorldDataService;

TextColumn::make('country.name')
    ->label(__('app.labels.country'))
    ->formatStateUsing(function ($record, WorldDataService $worldData) {
        if (!$record->country) {
            return '—';
        }
        
        $flag = $worldData->getCountryFlag($record->country->iso2);
        return "{$flag} {$record->country->name}";
    })
```

### Postal Code Validation in Forms

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

TextInput::make('postal_code')
    ->label(__('app.labels.postal_code'))
    ->rules([
        fn (Get $get, WorldDataService $worldData): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $worldData) {
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


### Distance-Based Filtering

```php
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

Filter::make('nearby')
    ->form([
        Select::make('city_id')
            ->label('Near City')
            ->options(fn (WorldDataService $worldData) => 
                $worldData->searchCities('')->pluck('name', 'id')
            )
            ->searchable(),
        TextInput::make('radius')
            ->label('Within (km)')
            ->numeric()
            ->default(50),
    ])
    ->query(function (Builder $query, array $data, WorldDataService $worldData): Builder {
        if (!isset($data['city_id']) || !isset($data['radius'])) {
            return $query;
        }
        
        // Get all cities within radius
        $nearbyCityIds = City::all()->filter(function ($city) use ($data, $worldData) {
            $distance = $worldData->getDistanceBetweenCities($data['city_id'], $city->id);
            return $distance !== null && $distance <= $data['radius'];
        })->pluck('id');
        
        return $query->whereIn('city_id', $nearbyCityIds);
    })
```

### Formatted Address Display

```php
use Filament\Infolists\Components\TextEntry;

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

## Testing

```php
use App\Services\World\WorldDataService;

it('validates postal codes correctly', function () {
    $service = app(WorldDataService::class);
    
    expect($service->validatePostalCode('10001', 'US'))->toBeTrue();
    expect($service->validatePostalCode('ABC', 'US'))->toBeFalse();
    expect($service->validatePostalCode('SW1A 1AA', 'GB'))->toBeTrue();
});

it('calculates distance between cities', function () {
    $service = app(WorldDataService::class);
    
    $distance = $service->getDistanceBetweenCities($cityId1, $cityId2);
    
    expect($distance)->toBeFloat();
    expect($distance)->toBeGreaterThan(0);
});

it('returns country flags', function () {
    $service = app(WorldDataService::class);
    
    $flag = $service->getCountryFlag('US');
    
    expect($flag)->toBe('🇺🇸');
});
```

## Related Documentation
- `docs/world-data-integration.md` - Complete integration guide
- `.kiro/steering/world-data-package.md` - Usage patterns
- `.kiro/steering/filament-forms-inputs.md` - Filament form patterns
