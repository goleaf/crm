# Blasp Profanity Filter Integration

## Overview

The Blasp profanity filter package (`blaspsoft/blasp` v3.1.0) is fully integrated into the application, providing powerful multi-language profanity detection and filtering capabilities. This document covers the complete integration including services, validation rules, Filament components, and best practices.

## Package Features

- **Multi-Language Support**: English, Spanish, German, French, and all-languages mode
- **Method Chaining**: Fluent API with `Blasp::spanish()->check()`
- **Custom Masking**: Configure custom mask characters
- **High Performance**: Advanced caching with O(1) lookups
- **Smart Detection**: Handles substitutions, separators, variations, and false positives
- **Laravel Integration**: Custom validation rules and service container integration

## Architecture

### Service Layer

The `ProfanityFilterService` wraps the Blasp package with application-specific logic:

```php
use App\Services\Content\ProfanityFilterService;

// Registered as singleton in AppServiceProvider
$service = app(ProfanityFilterService::class);

// Or inject via constructor
public function __construct(
    private readonly ProfanityFilterService $profanityFilter
) {}
```

### Key Methods

```php
// Check if text contains profanity
$hasProfanity = $service->hasProfanity('text to check', 'english');

// Clean text by masking profanities
$cleaned = $service->clean('text to clean', 'spanish', '#');

// Get detailed analysis
$analysis = $service->analyze('text to analyze', 'german');
// Returns: ['has_profanity', 'count', 'unique_profanities', 'clean_text', 'original_text']

// Check against all languages
$result = $service->checkAllLanguages('multilingual text');

// Validate and clean in one operation
$result = $service->validateAndClean('text', 'french', logViolations: true);
// Returns: ['valid', 'clean_text', 'profanities_found']

// Batch check multiple texts
$results = $service->batchCheck(['text1', 'text2', 'text3'], 'english');

// Cached check with TTL
$hasProfanity = $service->cachedCheck('text', 'english', ttl: 3600);

// Clear cache
$service->clearCache(); // Clear all
$service->clearCache('specific text', 'english'); // Clear specific
```

## Validation

### Custom Validation Rule

```php
use App\Rules\NoProfanity;

// Basic usage
$request->validate([
    'comment' => ['required', 'string', new NoProfanity()],
]);

// With specific language
$request->validate([
    'message' => ['required', new NoProfanity('spanish')],
]);

// Check all languages
$request->validate([
    'content' => ['required', new NoProfanity('all')],
]);

// With logging disabled
$request->validate([
    'text' => ['required', new NoProfanity(logViolations: false)],
]);
```

### Laravel Validation Rule

```php
// Using built-in Blasp validation rule
$request->validate([
    'comment' => 'required|string|blasp_check',
]);

// With language parameter
$request->validate([
    'message' => 'required|string|blasp_check:spanish',
]);
```

## Filament Integration

### Reusable Actions

#### Single Record Action

```php
use App\Filament\Actions\CleanProfanityAction;

// In resource table actions
public static function table(Table $table): Table
{
    return $table
        ->actions([
            CleanProfanityAction::make('description'),
            // Other actions...
        ]);
}

// In page header actions
protected function getHeaderActions(): array
{
    return [
        CleanProfanityAction::make('content'),
    ];
}
```

#### Bulk Action

```php
use App\Filament\Actions\CleanProfanityAction;

public static function table(Table $table): Table
{
    return $table
        ->bulkActions([
            CleanProfanityAction::makeBulk('description'),
        ]);
}
```

### Settings Page

A dedicated Filament page is available for testing and managing the profanity filter:

**Navigation**: Settings → Profanity Filter

**Features**:
- Test profanity detection with custom text
- Select language (English, Spanish, German, French, All)
- Configure custom mask character
- View detailed statistics (count, unique profanities)
- Clear profanity check cache

**Access**: `app/Filament/Pages/ProfanityFilterSettings.php`

### Form Validation

```php
use App\Rules\NoProfanity;
use Filament\Forms\Components\Textarea;

Textarea::make('comment')
    ->label(__('app.labels.comment'))
    ->rules([new NoProfanity('english')])
    ->helperText(__('app.helpers.no_profanity'))
    ->live(onBlur: true);
```

## Configuration

### Published Config

Location: `config/blasp.php`

```php
return [
    // Default language for detection
    'default_language' => 'english',
    
    // Default mask character
    'mask_character' => '*',
    
    // Cache driver (useful for Laravel Vapor with DynamoDB limits)
    'cache_driver' => env('BLASP_CACHE_DRIVER'),
    
    // Character separators
    'separators' => ['@', '#', '%', '&', '_', ...],
    
    // Character substitutions
    'substitutions' => [
        '/a/' => ['a', '4', '@', 'Á', 'á', ...],
        // ...
    ],
    
    // False positives (words that should not be flagged)
    'false_positives' => [
        'hello',
        'scunthorpe',
        'cockburn',
        // ...
    ],
];
```

### Language Files

Location: `config/languages/`

- `english.php` - English profanities
- `spanish.php` - Spanish profanities
- `german.php` - German profanities
- `french.php` - French profanities

Each language file contains:
- `profanities` - List of profane words
- `false_positives` - Language-specific false positives
- `substitutions` - Language-specific character substitutions

### Environment Variables

```env
# Cache driver for Blasp (optional)
BLASP_CACHE_DRIVER=redis

# Default language (optional, defaults to 'english')
BLASP_DEFAULT_LANGUAGE=english

# Default mask character (optional, defaults to '*')
BLASP_MASK_CHARACTER=*
```

## Usage Examples

### Basic Detection

```php
use App\Services\Content\ProfanityFilterService;

$service = app(ProfanityFilterService::class);

// Check for profanity
if ($service->hasProfanity($userInput)) {
    return response()->json(['error' => 'Inappropriate content'], 422);
}

// Clean and save
$cleanedText = $service->clean($userInput);
$model->update(['content' => $cleanedText]);
```

### Multi-Language Content

```php
// Check against all languages for international platforms
$result = $service->checkAllLanguages($userInput);

if ($result['has_profanity']) {
    Log::warning('Multi-language profanity detected', [
        'profanities' => $result['unique_profanities'],
        'count' => $result['count'],
    ]);
    
    return $result['clean_text'];
}
```

### Form Request Validation

```php
namespace App\Http\Requests;

use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:1000',
                new NoProfanity('all'), // Check all languages
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                new NoProfanity(), // Use default language
            ],
        ];
    }
}
```

### Batch Processing

```php
// Process multiple user submissions
$comments = Comment::where('moderated', false)->pluck('body')->toArray();

$results = $service->batchCheck($comments, 'english');

foreach ($results as $index => $hasProfanity) {
    if ($hasProfanity) {
        Comment::where('id', $commentIds[$index])
            ->update(['flagged' => true]);
    }
}
```

### Cached Checks

```php
// Cache profanity checks for frequently accessed content
$hasProfanity = $service->cachedCheck(
    $article->content,
    'english',
    ttl: 3600 // 1 hour
);

if ($hasProfanity) {
    // Handle flagged content
}
```

## Testing

### Unit Tests

Location: `tests/Unit/Services/ProfanityFilterServiceTest.php`

```bash
# Run profanity filter tests
php artisan test --filter=ProfanityFilterService

# Run validation rule tests
php artisan test --filter=NoProfanityRule
```

### Feature Tests

Location: `tests/Feature/Validation/NoProfanityRuleTest.php`

```bash
# Run all profanity-related tests
php artisan test tests/Unit/Services/ProfanityFilterServiceTest.php
php artisan test tests/Feature/Validation/NoProfanityRuleTest.php
```

### Test Coverage

```bash
# Run with coverage
php artisan test --coverage --min=80
```

## Performance Optimization

### Caching Strategy

The service includes built-in caching:

```php
// Automatic caching with custom TTL
$result = $service->cachedCheck($text, 'english', ttl: 7200);

// Clear cache when profanity lists are updated
$service->clearCache();
```

### Batch Operations

For bulk processing, use batch methods to reduce overhead:

```php
// More efficient than individual checks
$results = $service->batchCheck($texts, 'spanish');
```

### Cache Driver Configuration

For high-volume applications or Laravel Vapor:

```env
# Use Redis for better performance
BLASP_CACHE_DRIVER=redis

# Or use array driver for testing
BLASP_CACHE_DRIVER=array
```

## Artisan Commands

### Clear Blasp Cache

```bash
# Clear all Blasp expression caches
php artisan blasp:clear
```

This command clears:
- Cached profanity expressions
- Cached configurations
- Language-specific caches

## Best Practices

### DO:

✅ Use the service layer (`ProfanityFilterService`) instead of Blasp facade directly
✅ Register service as singleton in `AppServiceProvider`
✅ Use validation rules in Form Requests
✅ Log profanity violations for moderation review
✅ Cache frequently checked content
✅ Use batch methods for bulk operations
✅ Test with multiple languages for international apps
✅ Configure custom false positives for your domain
✅ Clear cache after updating profanity lists

### DON'T:

❌ Don't use Blasp facade directly in business logic
❌ Don't skip validation on user-generated content
❌ Don't forget to handle edge cases (empty strings, null values)
❌ Don't ignore performance implications of checking large texts
❌ Don't hardcode profanity lists in application code
❌ Don't skip logging for compliance/moderation
❌ Don't use synchronous checks for real-time chat (consider queues)
❌ Don't forget to test with actual profane content

## Translations

All user-facing text uses Laravel translations:

### English Translations

Location: `lang/en/app.php`

```php
'actions' => [
    'clean_profanity' => 'Clean Profanity',
    'test_filter' => 'Test Filter',
    'clear_cache' => 'Clear Cache',
],

'labels' => [
    'language' => 'Language',
    'mask_character' => 'Mask Character',
    'text_to_clean' => 'Text to Clean',
    'cleaned_text' => 'Cleaned Text',
    'profanities_found' => 'Profanities Found',
],

'languages' => [
    'english' => 'English',
    'spanish' => 'Spanish',
    'german' => 'German',
    'french' => 'French',
    'all' => 'All Languages',
],

'notifications' => [
    'profanity_cleaned' => 'Profanity Cleaned',
    'profanity_detected' => 'Profanity Detected',
    'no_profanity_found' => 'No Profanity Found',
],
```

### Validation Messages

Location: `lang/en/validation.php`

```php
'no_profanity' => 'The :attribute contains inappropriate language.',
```

## Security Considerations

### Logging

Profanity violations are logged by default:

```php
// Logged automatically when logViolations: true
$result = $service->validateAndClean($text, logViolations: true);

// Log entry includes:
// - Profanities found
// - Count
// - Language used
```

### Rate Limiting

Consider rate limiting profanity checks for public endpoints:

```php
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('throttle:10,1'); // 10 requests per minute
```

### Content Moderation

Use profanity detection as part of a broader moderation strategy:

```php
if ($service->hasProfanity($content)) {
    // Flag for manual review
    $model->update(['requires_moderation' => true]);
    
    // Notify moderators
    Notification::send($moderators, new ContentFlagged($model));
}
```

## Troubleshooting

### Issue: False Positives

**Solution**: Add words to false positives list in `config/blasp.php`:

```php
'false_positives' => [
    'your_word_here',
    // ...
],
```

### Issue: Performance Degradation

**Solution**: Enable caching and use appropriate cache driver:

```env
BLASP_CACHE_DRIVER=redis
```

### Issue: Language Not Detected

**Solution**: Ensure language file exists and is properly configured:

```bash
# Check if language file exists
ls config/languages/spanish.php

# Republish if missing
php artisan vendor:publish --tag="blasp-languages" --force
```

### Issue: Cache Size Limits (Laravel Vapor)

**Solution**: Use Redis instead of DynamoDB:

```env
BLASP_CACHE_DRIVER=redis
```

## Related Documentation

- Package README: `vendor/blaspsoft/blasp/README.md`
- Service Container: `docs/laravel-container-services.md`
- Validation: `docs/laravel-precognition.md`
- Filament Actions: `.kiro/steering/filament-conventions.md`

## Package Information

- **Package**: `blaspsoft/blasp`
- **Version**: v3.1.0
- **License**: MIT
- **Repository**: https://github.com/Blaspsoft/blasp
- **Documentation**: https://github.com/Blaspsoft/blasp/blob/main/README.md

## Support

For package-specific issues, refer to:
- GitHub Issues: https://github.com/Blaspsoft/blasp/issues
- Package Documentation: https://github.com/Blaspsoft/blasp

For application-specific integration issues, contact the development team.
