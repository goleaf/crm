<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\UnsplashAsset;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('creates unsplash asset from api response', function (): void {
    $apiData = [
        'id' => 'test-photo-123',
        'slug' => 'test-photo',
        'description' => 'A beautiful landscape',
        'alt_description' => 'Mountains at sunset',
        'urls' => [
            'raw' => 'https://example.com/raw.jpg',
            'full' => 'https://example.com/full.jpg',
            'regular' => 'https://example.com/regular.jpg',
            'small' => 'https://example.com/small.jpg',
            'thumb' => 'https://example.com/thumb.jpg',
        ],
        'links' => [
            'self' => 'https://api.unsplash.com/photos/test-photo-123',
            'html' => 'https://unsplash.com/photos/test-photo-123',
            'download' => 'https://unsplash.com/photos/test-photo-123/download',
            'download_location' => 'https://api.unsplash.com/photos/test-photo-123/download',
        ],
        'width' => 4000,
        'height' => 3000,
        'color' => '#2C3E50',
        'likes' => 150,
        'liked_by_user' => false,
        'user' => [
            'name' => 'John Photographer',
            'username' => 'johnphoto',
            'links' => [
                'html' => 'https://unsplash.com/@johnphoto',
            ],
        ],
    ];

    $asset = UnsplashAsset::createFromApi($apiData);

    expect($asset)
        ->toBeInstanceOf(UnsplashAsset::class)
        ->unsplash_id->toBe('test-photo-123')
        ->slug->toBe('test-photo')
        ->description->toBe('A beautiful landscape')
        ->photographer_name->toBe('John Photographer')
        ->photographer_username->toBe('johnphoto')
        ->width->toBe(4000)
        ->height->toBe(3000)
        ->likes->toBe(150);
});

it('finds or creates asset from api response', function (): void {
    $apiData = [
        'id' => 'existing-photo',
        'user' => [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'links' => ['html' => 'https://unsplash.com/@janedoe'],
        ],
    ];

    // First call creates
    $asset1 = UnsplashAsset::findOrCreateFromApi($apiData);

    // Second call finds existing
    $asset2 = UnsplashAsset::findOrCreateFromApi($apiData);

    expect($asset1->id)->toBe($asset2->id);
    expect(UnsplashAsset::where('unsplash_id', 'existing-photo')->count())->toBe(1);
});

it('gets url for different sizes', function (): void {
    $asset = UnsplashAsset::factory()->create([
        'urls' => [
            'raw' => 'https://example.com/raw.jpg',
            'regular' => 'https://example.com/regular.jpg',
            'thumb' => 'https://example.com/thumb.jpg',
        ],
    ]);

    expect($asset->getUrl('regular'))->toBe('https://example.com/regular.jpg');
    expect($asset->getUrl('thumb'))->toBe('https://example.com/thumb.jpg');
    expect($asset->getUrl('nonexistent'))->toBe('https://example.com/regular.jpg'); // Falls back to regular
});

it('checks if asset is downloaded', function (): void {
    $notDownloaded = UnsplashAsset::factory()->create([
        'local_path' => null,
        'downloaded_at' => null,
    ]);

    $downloaded = UnsplashAsset::factory()->create([
        'local_path' => 'unsplash/photo.jpg',
        'downloaded_at' => now(),
    ]);

    expect($notDownloaded->isDownloaded())->toBeFalse();
    expect($downloaded->isDownloaded())->toBeTrue();
});

it('gets local url when downloaded', function (): void {
    Storage::disk('public')->put('unsplash/test.jpg', 'content');

    $asset = UnsplashAsset::factory()->create([
        'local_path' => 'unsplash/test.jpg',
        'downloaded_at' => now(),
    ]);

    $localUrl = $asset->getLocalUrl();

    expect($localUrl)->toBeString();
    expect($localUrl)->toContain('unsplash/test.jpg');
});

it('returns null for local url when not downloaded', function (): void {
    $asset = UnsplashAsset::factory()->create([
        'local_path' => null,
    ]);

    expect($asset->getLocalUrl())->toBeNull();
});

it('generates attribution text', function (): void {
    $asset = UnsplashAsset::factory()->create([
        'photographer_name' => 'John Doe',
    ]);

    $attribution = $asset->getAttributionText();

    expect($attribution)->toBe('Photo by John Doe on Unsplash');
});

it('generates attribution html with utm parameters', function (): void {
    $asset = UnsplashAsset::factory()->create([
        'photographer_name' => 'Jane Smith',
        'photographer_url' => 'https://unsplash.com/@janesmith',
    ]);

    $html = $asset->getAttributionHtml();

    expect($html)
        ->toContain('Jane Smith')
        ->toContain('utm_source')
        ->toContain('utm_medium=referral')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener"');
});

it('can attach to models with trait', function (): void {
    $company = Company::factory()->create();
    $asset = UnsplashAsset::factory()->create();

    $company->attachUnsplashAsset($asset, collection: 'logo');

    expect($company->unsplashAssets)->toHaveCount(1);
    expect($company->firstUnsplashAsset('logo')->id)->toBe($asset->id);
});

it('can detach from models', function (): void {
    $company = Company::factory()->create();
    $asset = UnsplashAsset::factory()->create();

    $company->attachUnsplashAsset($asset);
    expect($company->unsplashAssets)->toHaveCount(1);

    $company->detachUnsplashAsset($asset);
    expect($company->unsplashAssets)->toHaveCount(0);
});

it('can sync assets with collection', function (): void {
    $company = Company::factory()->create();
    $asset1 = UnsplashAsset::factory()->create();
    $asset2 = UnsplashAsset::factory()->create();
    $asset3 = UnsplashAsset::factory()->create();

    // Attach initial assets
    $company->attachUnsplashAsset($asset1, collection: 'gallery');
    $company->attachUnsplashAsset($asset2, collection: 'gallery');

    // Sync to replace with new set
    $company->syncUnsplashAssets([$asset2->id, $asset3->id], collection: 'gallery');

    $galleryAssets = $company->unsplashAssetsInCollection('gallery')->get();

    expect($galleryAssets)->toHaveCount(2);
    expect($galleryAssets->pluck('id')->toArray())->toContain($asset2->id, $asset3->id);
    expect($galleryAssets->pluck('id')->toArray())->not->toContain($asset1->id);
});

it('maintains order in collections', function (): void {
    $company = Company::factory()->create();
    $asset1 = UnsplashAsset::factory()->create();
    $asset2 = UnsplashAsset::factory()->create();
    $asset3 = UnsplashAsset::factory()->create();

    $company->attachUnsplashAsset($asset1, collection: 'gallery', order: 2);
    $company->attachUnsplashAsset($asset2, collection: 'gallery', order: 0);
    $company->attachUnsplashAsset($asset3, collection: 'gallery', order: 1);

    $orderedAssets = $company->unsplashAssetsInCollection('gallery')->get();

    expect($orderedAssets->pluck('id')->toArray())->toEqual([
        $asset2->id,
        $asset3->id,
        $asset1->id,
    ]);
});

it('checks if model has unsplash assets', function (): void {
    $company = Company::factory()->create();
    $asset = UnsplashAsset::factory()->create();

    expect($company->hasUnsplashAssets())->toBeFalse();

    $company->attachUnsplashAsset($asset, collection: 'logo');

    expect($company->hasUnsplashAssets())->toBeTrue();
    expect($company->hasUnsplashAssets('logo'))->toBeTrue();
    expect($company->hasUnsplashAssets('gallery'))->toBeFalse();
});

it('stores metadata in pivot table', function (): void {
    $company = Company::factory()->create();
    $asset = UnsplashAsset::factory()->create();

    $company->attachUnsplashAsset(
        $asset,
        collection: 'featured',
        order: 0,
        metadata: ['caption' => 'Company headquarters', 'alt' => 'Building exterior'],
    );

    $pivot = $company->unsplashAssets()->first()->pivot;

    expect($pivot->metadata)
        ->toBeArray()
        ->toHaveKey('caption')
        ->and($pivot->metadata['caption'])->toBe('Company headquarters');
});

it('soft deletes assets', function (): void {
    $asset = UnsplashAsset::factory()->create();
    $assetId = $asset->id;

    $asset->delete();

    expect(UnsplashAsset::find($assetId))->toBeNull();
    expect(UnsplashAsset::withTrashed()->find($assetId))->not->toBeNull();
});
