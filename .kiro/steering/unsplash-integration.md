# Unsplash Integration Guidelines

## Service Usage
- Use `UnsplashService` (singleton in container) for all Unsplash API interactions instead of direct HTTP calls.
- Inject via constructor: `public function __construct(private readonly UnsplashService $unsplash) {}`.
- All search/fetch methods return cached results (1-hour TTL by default); cache keys follow `unsplash:{type}:{hash}` pattern.
- Call `$unsplash->clearCache()` after configuration changes or when cache invalidation is needed.
- The service uses the centralized HTTP client pattern with automatic retries on 429/5xx errors.

## Model Integration
- Add `HasUnsplashAssets` trait to models that should have Unsplash images (e.g., `BlogPost`, `Product`, `Company`).
- Use polymorphic relationships via `unsplashables` pivot table with `collection`, `order`, and `metadata` columns.
- Always use `UnsplashAsset::findOrCreateFromApi()` to avoid duplicate entries for the same Unsplash photo.
- Track downloads with `$unsplash->trackDownload($downloadLocation)` per Unsplash API requirements.

## Filament Integration
- Use `Mansoor\FilamentUnsplashPicker\Forms\Components\UnsplashPickerField` for image selection in forms.
- Configure modal width, image size, and lifecycle hooks: `->imageSize('regular')`, `->beforeUpload()`, `->afterUpload()`.
- Display images in tables with `ImageColumn::make('asset.urls.thumb')` and proper sizing.
- Include photographer attribution in infolists and detail views using `$asset->getAttributionHtml()`.

## Attribution Requirements
- Always display photographer credits per Unsplash license terms.
- Use `$asset->getAttributionHtml()` for proper UTM-tagged links.
- Include attribution in exports, PDFs, and any public-facing displays.
- Never remove or obscure photographer names or Unsplash branding.

## Performance
- Enable caching: `UNSPLASH_CACHE_ENABLED=true` (default).
- Download frequently used images to local storage with `$unsplash->downloadPhoto()`.
- Use appropriate image sizes: `thumb` (200px), `small` (400px), `regular` (1080px), `full` (2000px+).
- Eager load `unsplashAssets` relationship when displaying multiple models with images.
- Queue large downloads to avoid blocking requests.

## Configuration
- Set API keys in `.env`: `UNSPLASH_ACCESS_KEY`, `UNSPLASH_SECRET_KEY`.
- Configure storage disk and path: `UNSPLASH_STORAGE_DISK=public`, `UNSPLASH_STORAGE_PATH=unsplash`.
- Adjust cache TTL via `UNSPLASH_CACHE_TTL` (default 3600 seconds).
- Enable auto-download: `UNSPLASH_AUTO_DOWNLOAD=true` to automatically save selected images locally.

## Testing
- Mock `UnsplashService` in unit tests to avoid API calls: `$this->mock(UnsplashService::class)`.
- Use `Http::fake()` for integration tests with Unsplash API endpoints.
- Test attribution display in feature tests to ensure compliance.
- Verify download tracking is called when images are saved.

## Security
- Never expose API keys in frontend code or version control.
- Validate file types and sizes before downloading.
- Use secure storage disks with proper access controls.
- Implement rate limiting to prevent API abuse.
- Monitor API usage to stay within free tier limits (50 requests/hour).

## Best Practices
- ✅ Use service methods instead of direct API calls
- ✅ Cache search results aggressively
- ✅ Download popular images to reduce API calls
- ✅ Always track downloads per Unsplash requirements
- ✅ Display proper attribution with UTM parameters
- ✅ Use appropriate image sizes for context
- ✅ Eager load relationships to avoid N+1 queries
- ❌ Don't skip download tracking
- ❌ Don't remove photographer attribution
- ❌ Don't hardcode API keys
- ❌ Don't ignore rate limits
- ❌ Don't store images without proper tracking

## Translation Keys
- Add Unsplash-related translations to `lang/*/app.php`:
  - `app.actions.select_from_unsplash` - Button label
  - `app.modals.select_unsplash_photo` - Modal heading
  - `app.labels.photographer` - Photographer label
  - `app.labels.unsplash_id` - Unsplash ID label
  - `app.placeholders.search_photos` - Search placeholder

## Related Documentation
- `docs/unsplash-integration.md` - Complete integration guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-forms-inputs.md` - Filament form patterns
- `.kiro/steering/laravel-conventions.md` - Laravel conventions

## Unsplash API Guidelines
- Follow Unsplash API terms: https://help.unsplash.com/en/articles/2511245-unsplash-api-guidelines
- Respect rate limits (50 requests/hour for free tier)
- Track all downloads via `download_location` endpoint
- Include UTM parameters in all attribution links
- Display photographer name and Unsplash branding
