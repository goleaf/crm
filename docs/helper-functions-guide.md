# Helper Functions Guide

## Overview

This application includes a comprehensive set of helper classes that provide utility functions for common operations. These helpers follow Laravel conventions and are designed to be used throughout the application.

## Available Helpers

### ValidationHelper

Located: `App\Support\Helpers\ValidationHelper`

Provides validation utilities for common data types.

**Key Methods:**
- `isEmail()` - Validate email addresses
- `isUrl()` - Validate URLs
- `isIp()` - Validate IP addresses
- `isPhone()` - Validate phone numbers
- `isCreditCard()` - Validate credit cards (Luhn algorithm)
- `isPostalCode()` - Validate postal codes by country
- `isDate()` - Validate date strings
- `isJson()` - Validate JSON strings
- `isUuid()` - Validate UUIDs
- `isSlug()` - Validate URL slugs
- `isHexColor()` - Validate hex colors
- `isMacAddress()` - Validate MAC addresses
- `isUsername()` - Validate usernames
- `validatePasswordStrength()` - Check password strength

**Usage Examples:**
```php
use App\Support\Helpers\ValidationHelper;

// Email validation
if (ValidationHelper::isEmail($email)) {
    // Valid email
}

// Phone validation
if (ValidationHelper::isPhone('+1-555-123-4567')) {
    // Valid phone
}

// Postal code validation
if (ValidationHelper::isPostalCode('12345', 'US')) {
    // Valid US zip code
}

// Password strength
$result = ValidationHelper::validatePasswordStrength($password);
// ['valid' => true, 'score' => 100, 'feedback' => []]
```

### HtmlHelper

Located: `App\Support\Helpers\HtmlHelper`

Provides HTML generation and manipulation utilities.

**Key Methods:**
- `safe()` - Create safe HTML strings
- `stripTags()` - Remove HTML tags
- `nl2br()` - Convert line breaks to <br>
- `link()` - Create links
- `externalLink()` - Create external links
- `image()` - Create image tags
- `mailto()` - Create mailto links
- `tel()` - Create tel links
- `truncate()` - Truncate HTML preserving tags
- `sanitize()` - Sanitize HTML (XSS prevention)
- `linkify()` - Convert URLs to links
- `badge()` - Create badge elements
- `avatar()` - Create avatar with initials

**Usage Examples:**
```php
use App\Support\Helpers\HtmlHelper;

// Create safe HTML
$html = HtmlHelper::safe('<strong>Bold text</strong>');

// External link
$link = HtmlHelper::externalLink('https://example.com', 'Visit Site');

// Mailto link
$email = HtmlHelper::mailto('contact@example.com', 'Email Us');

// Badge
$badge = HtmlHelper::badge('New', 'success');

// Avatar with initials
$avatar = HtmlHelper::avatar('John Doe', 40);

// Linkify text
$text = HtmlHelper::linkify('Check out https://example.com');
```

### ArrayHelper

Located: `App\Support\Helpers\ArrayHelper`

Provides array manipulation utilities wrapping Laravel's `Arr` facade with additional functionality.

**Key Methods:**
- `joinList()` - Join arrays, collections, or JSON strings into readable text
- `keyBy()` - Key an array by a specific attribute
- `pluck()` - Extract values using dot notation
- `first()` / `last()` - Get first/last matching items
- `get()` / `set()` / `forget()` - Nested array access with dot notation
- `has()` - Check if keys exist
- `flatten()` - Flatten multi-dimensional arrays
- `where()` - Filter arrays with callbacks
- `only()` / `except()` - Get subsets of arrays
- `groupBy()` - Group items by a key
- `sortByMultiple()` - Sort by multiple fields
- `isAssoc()` - Check if array is associative
- `wrap()` - Wrap value in array if needed

**Usage Examples:**
```php
use App\Support\Helpers\ArrayHelper;

// Join mixed data types
$result = ArrayHelper::joinList(['apple', 'banana', 'cherry'], ', ', ' and ');
// Output: "apple, banana and cherry"

// Handle JSON strings
$result = ArrayHelper::joinList('["red", "green", "blue"]');
// Output: "red, green, blue"

// Nested array access
$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$name = ArrayHelper::get($data, 'user.name'); // "John"

// Group by key
$users = [
    ['name' => 'John', 'role' => 'admin'],
    ['name' => 'Jane', 'role' => 'user'],
    ['name' => 'Bob', 'role' => 'admin'],
];
$grouped = ArrayHelper::groupBy($users, 'role');
// ['admin' => [...], 'user' => [...]]
```

### StringHelper

Located: `App\Support\Helpers\StringHelper`

Provides string manipulation and formatting utilities.

**Key Methods:**
- `wordWrap()` - Wrap text with HTML-safe breaks
- `limit()` - Truncate to character limit
- `words()` - Truncate by word count
- `title()` / `camel()` / `snake()` / `kebab()` / `studly()` - Case conversions
- `plural()` / `singular()` - Pluralization
- `random()` - Generate random strings
- `mask()` - Mask portions of strings
- `initials()` - Extract initials from names
- `highlight()` - Highlight search terms
- `plainText()` - Strip HTML tags
- `excerpt()` - Create excerpts from HTML

**Usage Examples:**
```php
use App\Support\Helpers\StringHelper;

// Word wrap with HTML breaks
$wrapped = StringHelper::wordWrap($longText, 80, '<br>', cutLongWords: true);

// Truncate text
$short = StringHelper::limit($text, 100); // "Text here..."
$words = StringHelper::words($text, 50); // First 50 words

// Case conversions
$camel = StringHelper::camel('hello_world'); // "helloWorld"
$snake = StringHelper::snake('HelloWorld'); // "hello_world"
$kebab = StringHelper::kebab('HelloWorld'); // "hello-world"

// Extract initials
$initials = StringHelper::initials('John Doe Smith', 2); // "JD"

// Highlight search terms
$highlighted = StringHelper::highlight($text, 'search term', 'bg-yellow-200');

// Create excerpt from HTML
$excerpt = StringHelper::excerpt($htmlContent, 200);
```

### DateHelper

Located: `App\Support\Helpers\DateHelper`

Provides date and time manipulation utilities.

**Key Methods:**
- `humanDate()` - Format dates for humans
- `ago()` - Relative time (e.g., "2 hours ago")
- `isPast()` / `isFuture()` / `isToday()` - Date checks
- `startOfDay()` / `endOfDay()` - Day boundaries
- `range()` - Create date ranges
- `businessDaysBetween()` - Calculate business days
- `formatRange()` - Format date ranges for display

**Usage Examples:**
```php
use App\Support\Helpers\DateHelper;

// Human-readable dates
$formatted = DateHelper::humanDate($date, 'M j, Y'); // "Jan 15, 2025"

// Relative time
$relative = DateHelper::ago($date); // "2 hours ago"

// Date checks
if (DateHelper::isPast($deadline)) {
    // Handle overdue
}

// Date ranges
$range = DateHelper::range($startDate, $endDate);
// ['start' => Carbon, 'end' => Carbon]

// Business days
$days = DateHelper::businessDaysBetween($start, $end); // 5

// Format ranges
$display = DateHelper::formatRange($start, $end);
// "Jan 1, 2025 - Jan 15, 2025"
```

### NumberHelper

Located: `App\Support\Helpers\NumberHelper`

Provides number formatting and manipulation utilities.

**Key Methods:**
- `currency()` - Format as currency
- `format()` - Format with thousands separator
- `percentage()` - Format as percentage
- `fileSize()` - Convert bytes to human-readable
- `abbreviate()` - Abbreviate large numbers (1K, 1M, etc.)
- `clamp()` - Clamp between min/max
- `inRange()` - Check if in range
- `ordinal()` - Format as ordinal (1st, 2nd, 3rd)

**Usage Examples:**
```php
use App\Support\Helpers\NumberHelper;

// Currency formatting
$price = NumberHelper::currency(1234.56, 'USD'); // "$1,234.56"

// Number formatting
$formatted = NumberHelper::format(1234567, 2); // "1,234,567.00"

// Percentage
$percent = NumberHelper::percentage(75.5); // "75.50%"

// File sizes
$size = NumberHelper::fileSize(1048576); // "1.00 MB"

// Abbreviate numbers
$short = NumberHelper::abbreviate(1500000); // "1.5M"

// Ordinals
$position = NumberHelper::ordinal(1); // "1st"
$position = NumberHelper::ordinal(22); // "22nd"
```

### ColorHelper

Located: `App\Support\Helpers\ColorHelper`

Provides color manipulation utilities.

**Key Methods:**
- `isLight()` / `isDark()` - Check color brightness
- `hexToRgb()` / `rgbToHex()` - Color conversions
- `lighten()` / `darken()` - Adjust brightness
- `contrastText()` - Get contrasting text color
- `isValidHex()` - Validate hex colors
- `random()` - Generate random colors

**Usage Examples:**
```php
use App\Support\Helpers\ColorHelper;

// Check brightness
if (ColorHelper::isLight('#ffffff')) {
    // Use dark text
}

// Convert colors
$rgb = ColorHelper::hexToRgb('#ff5733');
// ['r' => 255, 'g' => 87, 'b' => 51]

$hex = ColorHelper::rgbToHex(255, 87, 51); // "#ff5733"

// Adjust brightness
$lighter = ColorHelper::lighten('#ff5733', 20); // Lighter by 20%
$darker = ColorHelper::darken('#ff5733', 20); // Darker by 20%

// Get contrasting text
$textColor = ColorHelper::contrastText('#ff5733'); // "#ffffff" or "#000000"

// Generate random color
$random = ColorHelper::random(); // "#a3c2f1"
```

### UrlHelper

Located: `App\Support\Helpers\UrlHelper`

Provides URL manipulation and validation utilities.

**Key Methods:**
- `isExternal()` - Check if URL is external
- `addQuery()` - Add query parameters
- `signedRoute()` - Generate signed URLs
- `sanitize()` - Sanitize URLs
- `isValid()` - Validate URLs
- `domain()` - Extract domain
- `withUtm()` - Add UTM tracking parameters
- `shorten()` - Shorten URLs for display

**Usage Examples:**
```php
use App\Support\Helpers\UrlHelper;

// Check if external
if (UrlHelper::isExternal($url)) {
    // Open in new tab
}

// Add query parameters
$url = UrlHelper::addQuery('https://example.com', ['page' => 2, 'sort' => 'name']);
// "https://example.com?page=2&sort=name"

// Generate signed URL
$signed = UrlHelper::signedRoute('download', ['file' => 123], now()->addHour());

// Add UTM tracking
$tracked = UrlHelper::withUtm($url, [
    'source' => 'email',
    'medium' => 'newsletter',
    'campaign' => 'spring_sale',
]);

// Shorten for display
$short = UrlHelper::shorten('https://example.com/very/long/path', 30);
// "example.com/very/long..."
```

### FileHelper

Located: `App\Support\Helpers\FileHelper`

Provides file manipulation and validation utilities.

**Key Methods:**
- `extension()` - Get file extension
- `nameWithoutExtension()` - Get filename without extension
- `isImage()` / `isDocument()` / `isVideo()` / `isAudio()` - File type checks
- `mimeType()` - Get MIME type from extension
- `sanitizeFilename()` - Generate safe filenames
- `iconClass()` - Get Heroicon class for file type
- `size()` / `exists()` / `delete()` - Storage operations
- `temporaryUrl()` - Generate temporary URLs
- `validateUpload()` - Validate uploaded files

**Usage Examples:**
```php
use App\Support\Helpers\FileHelper;

// Get file info
$ext = FileHelper::extension('document.pdf'); // "pdf"
$name = FileHelper::nameWithoutExtension('document.pdf'); // "document"

// Check file types
if (FileHelper::isImage($filename)) {
    // Process as image
}

// Get MIME type
$mime = FileHelper::mimeType('document.pdf'); // "application/pdf"

// Sanitize filename
$safe = FileHelper::sanitizeFilename('My Document (2024).pdf');
// "my-document-2024.pdf"

// Get icon for file type
$icon = FileHelper::iconClass('document.pdf'); // "heroicon-o-document"

// Storage operations
$size = FileHelper::size('uploads/file.pdf'); // bytes
$exists = FileHelper::exists('uploads/file.pdf'); // true/false

// Validate uploads
$valid = FileHelper::validateUpload(
    $file,
    ['pdf', 'doc', 'docx'],
    5 * 1024 * 1024 // 5MB max
);
```

## Integration with Filament

All helpers are designed to work seamlessly with Filament v4.3+ components:

### Table Columns
```php
use App\Support\Helpers\ArrayHelper;
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\NumberHelper;

TextColumn::make('tags')
    ->formatStateUsing(fn (mixed $state) => ArrayHelper::joinList($state)),

TextColumn::make('created_at')
    ->formatStateUsing(fn ($state) => DateHelper::ago($state)),

TextColumn::make('price')
    ->formatStateUsing(fn ($state) => NumberHelper::currency($state)),
```

### Form Fields
```php
use App\Support\Helpers\StringHelper;

TextInput::make('slug')
    ->afterStateUpdated(fn ($state, $set) => 
        $set('slug', StringHelper::kebab($state))
    ),
```

### Infolist Entries
```php
use App\Support\Helpers\FileHelper;

TextEntry::make('attachment')
    ->icon(fn ($state) => FileHelper::iconClass($state)),
```

## Best Practices

1. **Use helpers for consistency** - Always use helpers instead of manual implementations
2. **Type safety** - All helpers include proper type hints and return types
3. **Null handling** - Helpers gracefully handle null values
4. **Performance** - Helpers are optimized and cached where appropriate
5. **Testing** - All helpers are fully tested

## Testing Helpers

```php
use App\Support\Helpers\StringHelper;

it('truncates text correctly', function () {
    $text = 'This is a long text that needs truncation';
    $result = StringHelper::limit($text, 20);
    
    expect($result)->toBe('This is a long text...');
});
```

## Adding New Helpers

When adding new helper methods:

1. Add to appropriate helper class in `app/Support/Helpers/`
2. Include PHPDoc with parameter and return types
3. Add usage examples to this documentation
4. Write tests in `tests/Unit/Support/Helpers/`
5. Update steering files if patterns change

## Related Documentation

- `docs/array-helpers.md` - ArrayHelper detailed guide
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `.kiro/steering/filament-conventions.md` - Filament integration patterns
