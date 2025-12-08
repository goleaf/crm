# Unsplash Integration Guide

## Overview

This application integrates with the Unsplash API to provide access to high-quality, royalty-free images. The integration includes:

- **Core Service**: `UnsplashService` for API interactions
- **Database Models**: `UnsplashAsset` for storing image metadata
- **Filament Integration**: Custom form fields and actions for image selection
- **Automatic Attribution**: Proper photographer credits per Unsplash guidelines

## Table of Contents

1. [Installation & Configuration](#installation--configuration)
2. [Service Usage](#service-usage)
3. [Model Integration](#model-integration)
4. [Filament Integration](#filament-integration)
5. [API Methods](#api-methods)
6. [Best Practices](#best-practices)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Installation & Configuration

### 1. Get Unsplash API Keys

1. Visit [Unsplash Developers](https://unsplash.com/oauth/applications)
2. Create a new application
3. Copy your Access Key and Secret Key

### 2. Environment Configuration

Add to your `.env` file:

```env
# Unsplash API Configuration
UNSPLASH_ACCESS_KEY=your_access_key_here
UNSPLASH_SECRET_KEY=your_secret_key_here
UNSPLASH_UTM_SOURCE="${APP_NAME}"

# HTTP Client Settings
UNSPLASH_HTTP_TIMEOUT=30
UNSPLASH_HTTP_RETRY_TIMES=3
UNSPLASH_HTTP_RETRY_SLEEP=1000

# Default Image Settings
UNSPLASH_DEFAULT_PER_PAGE=30
UNSPLASH_DEFAULT_ORIENTATION=  # landscape, portrait, squarish
UNSPLASH_DEFAULT_QUALITY=80
UNSPLASH_AUTO_DOWNLOAD=true

# Storage Configuration
UNSPLASH_STORAGE_DISK=public
UNSPLASH_STORAGE_PATH=unsplash

# Cache Configuration
UNSPLASH_CACHE_ENABLED=true
UNSPLASH_CACHE_TTL=3600
UNSPLASH_CACHE_PREFIX=unsplash

# Filament Integration
UNSPLASH_FILAMENT_ENABLED=true
UNSPLASH_FILAMENT_MODAL_WIDTH=xl
UNSPLASH_FILAMENT_COLUMNS_GRID=3
UNSPLASH_FILAMENT_SHOW_PHOTOGRAPHER=true
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates two tables:
- `unsplash_assets` - Stores image metadata
- `unsplashables` - Polymorphic pivot table for attaching images to models

---

## Service Usage

### Basic Service Injection

The `UnsplashService` follows the container pattern with constructor injection:

```php
use App\Services\Media\UnsplashService;

class MyController
{
    public function __construct(
        private readonly UnsplashService $unsplash
    ) {}
    
    public function index()
    {
        $photos = $this->unsplash->searchPhotos('nature', page: 1, perPage: 20);
        
        return view('gallery', ['photos' => $photos]);
    }
}
```

### Search Photos

```php
$results = $unsplash->searchPhotos(
    query: 'mountains',
    page: 1,
    perPage: 30,
    orientation: 'landscape', // landscape, portrait, squarish
    color: 'blue', // black, white, yellow, orange, red, purple, magenta, green, teal, blue
);

// Returns:
// [
//     'results' => [...],
//     'total' => 1234,
//     'total_pages' => 42
// ]
```

### Get Random Photo

```php
$photos = $unsplash->randomPhoto(
    query: 'nature',
    orientation: 'landscape',
    collections: ['123456'],
    count: 5
);
```

### Get Photo Details

```php
$photo = $unsplash->getPhoto('photo-id-here');

if ($photo) {
    echo $photo['description'];
    echo $photo['urls']['regular'];
}
```

### Download Photo

```php
$localPath = $unsplash->downloadPhoto(
    url: $photo['urls']['regular'],
    filename: 'my-photo.jpg',
    disk: 'public',
    path: 'images'
);

if ($localPath) {
    echo "Downloaded to: {$localPath}";
}
```

### Track Download (Required by Unsplash)

Per Unsplash API guidelines, you must track downloads:

```php
$unsplash->trackDownload($photo['links']['download_location']);
```

### Clear Cache

```php
// Clear specific cache key
$unsplash->clearCache('search');

// Clear all Unsplash cache
$unsplash->clearCache();
```

---

## Model Integration

### Add Trait to Models

Use the `HasUnsplashAssets` trait on any model that should have Unsplash images:

```php
use App\Models\Concerns\HasUnsplashAssets;

class BlogPost extends Model
{
    use HasUnsplashAssets;
}
```

### Available Methods

```php
// Get all Unsplash assets
$post->unsplashAssets;

// Get assets in a specific collection
$post->unsplashAssetsInCollection('featured');

// Attach an asset
$post->attachUnsplashAsset(
    asset: $asset,
    collection: 'featured',
    order: 0,
    metadata: ['caption' => 'Beautiful sunset']
);

// Detach an asset
$post->detachUnsplashAsset($asset, collection: 'featured');

// Sync assets (replaces existing)
$post->syncUnsplashAssets([1, 2, 3], collection: 'gallery');

// Check if has assets
if ($post->hasUnsplashAssets('featured')) {
    // ...
}

// Get first asset
$featuredImage = $post->firstUnsplashAsset('featured');
```

### Working with UnsplashAsset Model

```php
use App\Models\UnsplashAsset;

// Create from API response
$asset = UnsplashAsset::createFromApi($photoData);

// Find or create
$asset = UnsplashAsset::findOrCreateFromApi($photoData);

// Get image URL
$url = $asset->getUrl('regular'); // raw, full, regular, small, thumb

// Get local URL (if downloaded)
$localUrl = $asset->getLocalUrl();

// Check if downloaded
if ($asset->isDownloaded()) {
    // Use local file
}

// Get attribution
echo $asset->getAttributionText();
// "Photo by John Doe on Unsplash"

echo $asset->getAttributionHtml();
// <a href="...">Photo by John Doe</a> on <a href="...">Unsplash</a>
```

---

## Filament Integration

### Using the Filament Unsplash Picker

The package includes a pre-built Filament field for selecting Unsplash images:

```php
use Mansoor\FilamentUnsplashPicker\Forms\Components\UnsplashPickerField;

public static function form(Form $form): Form
{
    return $form->schema([
        UnsplashPickerField::make('featured_image')
            ->label(__('app.labels.featured_image'))
            ->imageSize('regular') // raw, full, regular, small, thumb
            ->beforeUpload(function (array $data) {
                // Hook before image is saved
            })
            ->afterUpload(function (UnsplashAsset $asset) {
                // Hook after image is saved
            }),
    ]);
}
```

### Custom Unsplash Action

Create a custom action for more control:

```php
use App\Services\Media\UnsplashService;
use Filament\Actions\Action;

Action::make('selectUnsplash')
    ->label(__('app.actions.select_from_unsplash'))
    ->icon('heroicon-o-photo')
    ->modalHeading(__('app.modals.select_unsplash_photo'))
    ->modalWidth('7xl')
    ->form([
        TextInput::make('search')
            ->label(__('app.labels.search'))
            ->placeholder(__('app.placeholders.search_photos'))
            ->live(debounce: 500),
        
        Select::make('orientation')
            ->label(__('app.labels.orientation'))
            ->options([
                'landscape' => __('app.options.landscape'),
                'portrait' => __('app.options.portrait'),
                'squarish' => __('app.options.squarish'),
            ])
            ->live(),
    ])
    ->action(function (array $data, UnsplashService $unsplash) {
        $results = $unsplash->searchPhotos(
            query: $data['search'],
            orientation: $data['orientation'] ?? null
        );
        
        // Process results...
    })
```

### Display Unsplash Images in Tables

```php
use Filament\Tables\Columns\ImageColumn;

ImageColumn::make('featured_image.urls.thumb')
    ->label(__('app.labels.image'))
    ->circular()
    ->size(60)
```

### Display in Infolists

```php
use Filament\Infolists\Components\ImageEntry;

ImageEntry::make('featured_image.urls.regular')
    ->label(__('app.labels.featured_image'))
    ->size(400)
```

---

## API Methods

### UnsplashService Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `searchPhotos()` | query, page, perPage, orientation, color | array | Search for photos |
| `randomPhoto()` | query, orientation, collections, count | array | Get random photo(s) |
| `getPhoto()` | id | ?array | Get photo details |
| `trackDownload()` | downloadLocation | bool | Track download (required) |
| `downloadPhoto()` | url, filename, disk, path | ?string | Download to storage |
| `searchCollections()` | query, page, perPage | array | Search collections |
| `getCollectionPhotos()` | id, page, perPage | array | Get collection photos |
| `clearCache()` | ?key | bool | Clear cache |

### UnsplashAsset Model Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getUrl(size)` | ?string | Get image URL for size |
| `getLocalUrl()` | ?string | Get local storage URL |
| `isDownloaded()` | bool | Check if downloaded |
| `getAttributionText()` | string | Get plain text attribution |
| `getAttributionHtml()` | string | Get HTML attribution |
| `createFromApi(data)` | self | Create from API response |
| `findOrCreateFromApi(data)` | self | Find or create from API |

---

## Best Practices

### ✅ DO:

1. **Always Track Downloads**
   ```php
   if ($asset->download_location) {
       $unsplash->trackDownload($asset->download_location);
   }
   ```

2. **Provide Attribution**
   ```blade
   <img src="{{ $asset->getUrl('regular') }}" alt="{{ $asset->alt_description }}">
   <p>{!! $asset->getAttributionHtml() !!}</p>
   ```

3. **Use Caching**
   - Cache is enabled by default
   - Reduces API calls
   - Improves performance

4. **Download Popular Images**
   ```php
   if (config('unsplash.defaults.auto_download')) {
       $unsplash->downloadPhoto($url, $filename);
   }
   ```

5. **Handle API Failures Gracefully**
   ```php
   $results = $unsplash->searchPhotos('nature');
   
   if (empty($results['results'])) {
       // Show fallback or error message
   }
   ```

6. **Use Appropriate Image Sizes**
   - `thumb` - 200px
   - `small` - 400px
   - `regular` - 1080px
   - `full` - 2000px+
   - `raw` - Original size

### ❌ DON'T:

1. **Don't Skip Download Tracking**
   - Required by Unsplash API terms
   - Helps photographers get credit

2. **Don't Hardcode API Keys**
   - Always use environment variables
   - Never commit keys to version control

3. **Don't Ignore Rate Limits**
   - Free tier: 50 requests/hour
   - Use caching to reduce calls

4. **Don't Remove Attribution**
   - Required by Unsplash license
   - Must credit photographer

5. **Don't Store Images Without Permission**
   - Download only when needed
   - Respect Unsplash terms of service

---

## Testing

### Unit Tests

```php
use App\Services\Media\UnsplashService;
use Illuminate\Support\Facades\Http;

it('searches photos successfully', function () {
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response([
            'results' => [
                ['id' => '123', 'description' => 'Test photo'],
            ],
            'total' => 1,
            'total_pages' => 1,
        ], 200),
    ]);
    
    $service = app(UnsplashService::class);
    $results = $service->searchPhotos('nature');
    
    expect($results['results'])->toHaveCount(1);
    expect($results['total'])->toBe(1);
});

it('handles API failures gracefully', function () {
    Http::fake([
        'api.unsplash.com/*' => Http::response([], 500),
    ]);
    
    $service = app(UnsplashService::class);
    $results = $service->searchPhotos('nature');
    
    expect($results['results'])->toBeEmpty();
});
```

### Feature Tests

```php
use App\Models\UnsplashAsset;
use App\Models\BlogPost;

it('can attach unsplash asset to model', function () {
    $post = BlogPost::factory()->create();
    $asset = UnsplashAsset::factory()->create();
    
    $post->attachUnsplashAsset($asset, collection: 'featured');
    
    expect($post->unsplashAssets)->toHaveCount(1);
    expect($post->firstUnsplashAsset('featured')->id)->toBe($asset->id);
});
```

### Filament Tests

```php
use function Pest\Livewire\livewire;

it('can select unsplash image in form', function () {
    $user = User::factory()->create();
    
    livewire(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Test Post',
            'featured_image' => 'unsplash-photo-id',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    expect(BlogPost::first()->featured_image)->toBe('unsplash-photo-id');
});
```

---

## Troubleshooting

### Issue: "Invalid Access Key"

**Solution**: Verify your `UNSPLASH_ACCESS_KEY` in `.env` is correct.

```bash
php artisan config:clear
php artisan cache:clear
```

### Issue: "Rate Limit Exceeded"

**Solution**: 
- Enable caching: `UNSPLASH_CACHE_ENABLED=true`
- Increase cache TTL: `UNSPLASH_CACHE_TTL=7200`
- Upgrade to Unsplash Plus for higher limits

### Issue: "Download Failed"

**Solution**: Check storage permissions and disk configuration:

```bash
php artisan storage:link
chmod -R 775 storage/app/public
```

### Issue: "Images Not Displaying"

**Solution**: Ensure storage is linked and URLs are correct:

```php
// Check if asset is downloaded
if ($asset->isDownloaded()) {
    $url = $asset->getLocalUrl();
} else {
    $url = $asset->getUrl('regular');
}
```

### Issue: "Attribution Not Showing"

**Solution**: Always include attribution in your views:

```blade
@if($asset)
    <div class="image-attribution">
        {!! $asset->getAttributionHtml() !!}
    </div>
@endif
```

---

## Performance Optimization

### 1. Enable Caching

```env
UNSPLASH_CACHE_ENABLED=true
UNSPLASH_CACHE_TTL=3600
```

### 2. Download Popular Images

```php
// Download images that will be used frequently
if ($asset->likes > 1000) {
    $unsplash->downloadPhoto($asset->getUrl('regular'), "{$asset->unsplash_id}.jpg");
}
```

### 3. Use Appropriate Image Sizes

```php
// For thumbnails
$asset->getUrl('thumb');

// For full-width images
$asset->getUrl('regular');
```

### 4. Eager Load Relationships

```php
$posts = BlogPost::with('unsplashAssets')->get();
```

### 5. Queue Downloads

```php
use Illuminate\Support\Facades\Queue;

Queue::push(function () use ($asset, $unsplash) {
    $unsplash->downloadPhoto($asset->getUrl('regular'), "{$asset->unsplash_id}.jpg");
});
```

---

## Security Considerations

1. **API Key Protection**
   - Never expose keys in frontend code
   - Use environment variables
   - Rotate keys periodically

2. **Rate Limiting**
   - Implement application-level rate limiting
   - Cache aggressively
   - Monitor API usage

3. **Storage Security**
   - Validate file types before saving
   - Use secure storage disks
   - Implement access controls

4. **Attribution Compliance**
   - Always display photographer credits
   - Include UTM parameters in links
   - Follow Unsplash API guidelines

---

## Related Documentation

- [Laravel Container Services](./laravel-container-services.md)
- [Filament Forms & Inputs](./.kiro/steering/filament-forms-inputs.md)
- [HTTP Client Configuration](./http-client-configuration.md)
- [Unsplash API Documentation](https://unsplash.com/documentation)
- [Unsplash API Guidelines](https://help.unsplash.com/en/articles/2511245-unsplash-api-guidelines)

---

## Support & Resources

- **Unsplash API**: https://unsplash.com/developers
- **Package Repository**: https://github.com/marksitko/laravel-unsplash
- **Filament Picker**: https://github.com/mansoorkhan96/filament-unsplash-picker
- **Issue Tracker**: [Your repository issues]

---

## License & Attribution

This integration uses the Unsplash API which requires:
- Proper photographer attribution
- UTM parameters in links
- Download tracking
- Compliance with Unsplash License

See [Unsplash License](https://unsplash.com/license) for full terms.
