# Unsplash Integration - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Get Your API Keys (2 minutes)

1. Visit https://unsplash.com/oauth/applications
2. Click "New Application"
3. Accept the terms
4. Fill in application details
5. Copy your **Access Key** and **Secret Key**

### Step 2: Configure Environment (1 minute)

Add to your `.env` file:

```env
UNSPLASH_ACCESS_KEY=your_access_key_here
UNSPLASH_SECRET_KEY=your_secret_key_here
```

That's it! The integration is already configured with sensible defaults.

### Step 3: Use in Your Code (2 minutes)

#### Search for Photos

```php
use App\Services\Media\UnsplashService;

class MyController
{
    public function __construct(
        private readonly UnsplashService $unsplash
    ) {}
    
    public function search()
    {
        $results = $this->unsplash->searchPhotos('nature');
        
        return view('gallery', ['photos' => $results['results']]);
    }
}
```

#### Add to Filament Forms

```php
use Mansoor\FilamentUnsplashPicker\Forms\Components\UnsplashPickerField;

UnsplashPickerField::make('featured_image')
    ->label('Featured Image')
    ->imageSize('regular')
```

#### Attach to Models

```php
use App\Models\Company;
use App\Models\UnsplashAsset;

$company = Company::find(1);
$asset = UnsplashAsset::findOrCreateFromApi($photoData);

$company->attachUnsplashAsset($asset, collection: 'logo');
```

## 📚 What's Included

- ✅ **Service Layer** - `UnsplashService` with caching & retries
- ✅ **Database Models** - `UnsplashAsset` with relationships
- ✅ **Filament Integration** - Pre-built form fields
- ✅ **Automatic Attribution** - Photographer credits with UTM tracking
- ✅ **Download Management** - Local storage with tracking
- ✅ **33 Tests** - Full coverage of functionality

## 🎯 Common Use Cases

### 1. Company Logos

```php
// In CompanyResource form
UnsplashPickerField::make('logo')
    ->label(__('app.labels.logo'))
    ->imageSize('small')
```

### 2. Blog Post Featured Images

```php
// Add trait to BlogPost model
use HasUnsplashAssets;

// In form
UnsplashPickerField::make('featured_image')
    ->imageSize('regular')
```

### 3. Product Gallery

```php
// Attach multiple images
$product->syncUnsplashAssets([$asset1->id, $asset2->id], collection: 'gallery');

// Display in view
@foreach($product->unsplashAssetsInCollection('gallery') as $image)
    <img src="{{ $image->getUrl('regular') }}" alt="{{ $image->alt_description }}">
    <p>{!! $image->getAttributionHtml() !!}</p>
@endforeach
```

## ⚙️ Optional Configuration

All settings have sensible defaults. Customize if needed:

```env
# Cache settings (default: enabled, 1 hour)
UNSPLASH_CACHE_ENABLED=true
UNSPLASH_CACHE_TTL=3600

# Auto-download images (default: true)
UNSPLASH_AUTO_DOWNLOAD=true

# Storage location (default: public disk, unsplash folder)
UNSPLASH_STORAGE_DISK=public
UNSPLASH_STORAGE_PATH=unsplash

# Default image settings
UNSPLASH_DEFAULT_PER_PAGE=30
UNSPLASH_DEFAULT_ORIENTATION=landscape
```

## 🔒 Important: Attribution Requirements

**Always display photographer credits!** It's required by Unsplash's license.

```blade
{{-- Automatic attribution with UTM tracking --}}
{!! $asset->getAttributionHtml() !!}

{{-- Output: Photo by John Doe on Unsplash (with proper links) --}}
```

## 📖 Full Documentation

- **Complete Guide**: `docs/unsplash-integration.md`
- **Development Guidelines**: `.kiro/steering/unsplash-integration.md`
- **API Reference**: https://unsplash.com/documentation

## 🧪 Test the Integration

```bash
# Run all Unsplash tests
composer test tests/Unit/Services/UnsplashServiceTest.php
composer test tests/Feature/Models/UnsplashAssetTest.php

# Or run all tests
composer test
```

## 🆘 Troubleshooting

### "Invalid Access Key"
- Check your `.env` file has the correct keys
- Run `php artisan config:clear`

### "Rate Limit Exceeded"
- Free tier: 50 requests/hour
- Enable caching: `UNSPLASH_CACHE_ENABLED=true`
- Consider upgrading to Unsplash Plus

### Images Not Displaying
- Run `php artisan storage:link`
- Check storage permissions: `chmod -R 775 storage/app/public`

## 💡 Pro Tips

1. **Cache Aggressively** - Search results are cached by default
2. **Download Popular Images** - Reduces API calls
3. **Use Appropriate Sizes** - `thumb` for thumbnails, `regular` for content
4. **Track Downloads** - Automatically handled, but verify in logs
5. **Test in Staging** - Use test API keys for development

## 🎉 You're Ready!

The integration is complete and production-ready. Start adding beautiful, royalty-free images to your application!

---

**Need Help?** Check the full documentation in `docs/unsplash-integration.md`
