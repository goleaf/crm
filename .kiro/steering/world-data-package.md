---
inclusion: always
---

# World Data Package Integration (nnjeim/world)

## Service Usage
- Use `WorldDataService` (singleton in container) for all world data access instead of querying models directly.
- Inject via constructor: `public function __construct(private readonly WorldDataService $worldData) {}`.
- All methods return cached results (1-hour TTL by default); cache keys follow `world.{entity}.{column}.{identifier}` pattern.
- Call `$worldData->clearCache()` after bulk data updates or when cache invalidation is needed.

## Enhanced Features
- **Regional Filtering**: `getCountriesByRegion()`, `getCountriesBySubregion()`, `getRegions()` for geographic grouping.
- **EU Countries**: `getEUCountries()` returns all European Union member states.
- **Phone Codes**: `getCountriesByPhoneCode()` for international dialing lookups.
- **Full Details**: `getCountryWithDetails()` eager loads currencies, languages, and timezones.
- **Address Formatting**: `formatAddress()` creates display-ready address strings.
- **Country Flags**: `getCountryFlag()` returns emoji flags from ISO2 codes.
- **Postal Validation**: `validatePostalCode()` validates formats for 50+ countries.
- **Distance Calculation**: `getDistanceBetweenCities()` uses Haversine formula for km distances.

## Filament Form Patterns
- Use dependent selects for country → state → city hierarchies with `->live()` and `->afterStateUpdated()` to clear child fields.
- Always include `->searchable()` and `->preload()` on world data selects for better UX.
- Inject `WorldDataService` in closures: `->options(fn (WorldDataService $worldData) => $worldData->getCountries()->pluck('name', 'id'))`.
- Use `Get $get` for dependent field logic: `->visible(fn (Get $get) => filled($get('country_id')))`.

## Performance
- Cache warming: preload popular countries with `$worldData->getPopularCountries()` for frequently accessed data.
- Paginate city queries when displaying large datasets; avoid loading all cities without state/country filter.
- Eager load relationships when displaying related data: `Country::with(['states', 'currencies'])->get()`.
- Use ISO codes for lookups (faster): `$worldData->getCountry('US', 'iso2')` instead of name matching.

## Configuration
- Enable/disable modules in `config/world.php` (`states`, `cities`, `timezones`, `currencies`, `languages`).
- Set `allowed_countries`/`disallowed_countries` arrays to limit data scope if needed.
- Configure `popular_countries` array for quick-access country lists (defaults: US, GB, CA, AU, DE, FR, ES, IT, JP, CN).
- Adjust cache TTL via `WORLD_CACHE_TTL` env var (default 3600 seconds).

## Testing
- Mock `WorldDataService` in unit tests to avoid database queries.
- Use `Cache::fake()` to test caching behavior.
- Test dependent select logic with Livewire component tests.
- Verify cache keys are set/cleared correctly.

## Translation
- World data labels use package translations in `resources/lang/vendor/world/`.
- Form labels use app translations: `->label(__('app.labels.country'))`.
- Keep field names consistent: `country_id`, `state_id`, `city_id`, `currency_id`, `timezone_id`.

## Best Practices
- ✅ Use service methods instead of direct model queries
- ✅ Cache frequently accessed data
- ✅ Clear dependent fields when parent changes
- ✅ Use ISO codes for country lookups
- ✅ Paginate large city datasets
- ✅ Eager load relationships
- ❌ Don't query world models directly in resources
- ❌ Don't skip caching for repeated queries
- ❌ Don't load all cities without filtering
- ❌ Don't hardcode country/currency lists

## Related Documentation
- `docs/world-data-integration.md` - Complete integration guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-forms-inputs.md` - Filament form patterns
