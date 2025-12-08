# Blasp Profanity Filter Integration

## Service Usage
- Use `ProfanityFilterService` (singleton in container) for all profanity detection instead of Blasp facade directly.
- Inject via constructor: `public function __construct(private readonly ProfanityFilterService $profanityFilter) {}`.
- All methods support multi-language detection (English, Spanish, German, French, All Languages).
- Service includes caching, logging, and batch processing capabilities.

## Validation Patterns
- Use `NoProfanity` rule in Form Requests: `new NoProfanity('spanish')` or `new NoProfanity('all')`.
- Laravel validation rule available: `'field' => 'required|blasp_check:spanish'`.
- Always validate user-generated content (comments, posts, messages, reviews, descriptions).
- Log violations for moderation review: `validateAndClean($text, logViolations: true)`.

## Filament Integration
- Use `CleanProfanityAction::make('field_name')` for single record cleaning in table actions.
- Use `CleanProfanityAction::makeBulk('field_name')` for bulk operations.
- Settings page available at Settings → Profanity Filter for testing and cache management.
- Form validation: `->rules([new NoProfanity()])` with `->live(onBlur: true)` for real-time feedback.

## Performance
- Cache frequently checked content: `$service->cachedCheck($text, 'english', ttl: 3600)`.
- Use batch methods for bulk operations: `$service->batchCheck($texts, 'spanish')`.
- Configure cache driver via `BLASP_CACHE_DRIVER` env var (use Redis for high-volume apps).
- Clear cache after updating profanity lists: `php artisan blasp:clear` or `$service->clearCache()`.

## Configuration
- Default language set in `config/blasp.php` (`default_language`).
- Custom mask character configurable: `config/blasp.php` (`mask_character`) or `$service->clean($text, maskCharacter: '#')`.
- Language-specific profanity lists in `config/languages/{language}.php`.
- False positives configured in `config/blasp.php` (`false_positives` array).

## Multi-Language Support
- Check specific language: `$service->hasProfanity($text, 'german')`.
- Check all languages: `$service->checkAllLanguages($text)` for international platforms.
- Language shortcuts available: `Blasp::spanish()->check()`, `Blasp::french()->check()`.
- Each language includes character normalization (accents, umlauts, cedillas).

## Testing
- Unit tests: `tests/Unit/Services/ProfanityFilterServiceTest.php`.
- Feature tests: `tests/Feature/Validation/NoProfanityRuleTest.php`.
- Test with actual profane content to verify detection.
- Mock service in unit tests to avoid database queries.

## Best Practices
- ✅ Use service layer instead of Blasp facade directly
- ✅ Validate all user-generated content
- ✅ Log violations for compliance/moderation
- ✅ Cache frequently checked content
- ✅ Use batch methods for bulk operations
- ✅ Configure custom false positives for your domain
- ✅ Test with multiple languages for international apps
- ❌ Don't skip validation on user input
- ❌ Don't use synchronous checks for real-time chat (consider queues)
- ❌ Don't forget to handle edge cases (empty strings, null values)
- ❌ Don't hardcode profanity lists in application code

## Translations
- All labels use `lang/en/app.php`: `__('app.actions.clean_profanity')`, `__('app.labels.language')`.
- Validation messages in `lang/en/validation.php`: `__('validation.no_profanity')`.
- Language names: `__('app.languages.english')`, `__('app.languages.spanish')`, etc.

## Related Documentation
- `docs/blasp-profanity-filter-integration.md` - Complete integration guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-conventions.md` - Filament action patterns
- `.kiro/steering/translations.md` - Translation conventions

## Package Information
- Package: `blaspsoft/blasp` v3.1.0
- Service: `App\Services\Content\ProfanityFilterService`
- Validation Rule: `App\Rules\NoProfanity`
- Filament Actions: `App\Filament\Actions\CleanProfanityAction`
- Settings Page: `App\Filament\Pages\ProfanityFilterSettings`
