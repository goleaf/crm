<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

final class UnsplashAsset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'unsplash_id',
        'slug',
        'description',
        'alt_description',
        'urls',
        'links',
        'width',
        'height',
        'color',
        'likes',
        'liked_by_user',
        'photographer_name',
        'photographer_username',
        'photographer_url',
        'download_location',
        'local_path',
        'downloaded_at',
        'exif',
        'location',
        'tags',
    ];

    protected $casts = [
        'urls' => 'array',
        'links' => 'array',
        'exif' => 'array',
        'location' => 'array',
        'tags' => 'array',
        'liked_by_user' => 'boolean',
        'downloaded_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('unsplash.tables.assets', 'unsplash_assets');
    }

    /**
     * Get the URL for the specified size
     */
    public function getUrl(string $size = 'regular'): ?string
    {
        return $this->urls[$size] ?? $this->urls['regular'] ?? null;
    }

    /**
     * Get the local file URL if downloaded
     */
    public function getLocalUrl(): ?string
    {
        if (! $this->local_path) {
            return null;
        }

        $disk = config('unsplash.storage.disk');

        return Storage::disk($disk)->url($this->local_path);
    }

    /**
     * Check if the asset has been downloaded locally
     */
    public function isDownloaded(): bool
    {
        return $this->local_path !== null && $this->downloaded_at !== null;
    }

    /**
     * Get photographer attribution text
     */
    public function getAttributionText(): string
    {
        return "Photo by {$this->photographer_name} on Unsplash";
    }

    /**
     * Get photographer attribution HTML
     */
    public function getAttributionHtml(): string
    {
        return sprintf(
            'Photo by <a href="%s?utm_source=%s&utm_medium=referral" target="_blank" rel="noopener">%s</a> on <a href="https://unsplash.com?utm_source=%s&utm_medium=referral" target="_blank" rel="noopener">Unsplash</a>',
            $this->photographer_url,
            config('unsplash.utm_source'),
            $this->photographer_name,
            config('unsplash.utm_source'),
        );
    }

    /**
     * Polymorphic relation to models using this asset
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<\App\Models\Model, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function models(): MorphToMany
    {
        return $this->morphedByMany(
            Model::class,
            'unsplashable',
            config('unsplash.tables.pivot', 'unsplashables'),
        )->withPivot(['collection', 'order', 'metadata'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Create from Unsplash API response
     */
    public static function createFromApi(array $data): self
    {
        return self::create([
            'unsplash_id' => $data['id'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'alt_description' => $data['alt_description'] ?? null,
            'urls' => $data['urls'] ?? [],
            'links' => $data['links'] ?? [],
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'color' => $data['color'] ?? null,
            'likes' => $data['likes'] ?? 0,
            'liked_by_user' => $data['liked_by_user'] ?? false,
            'photographer_name' => $data['user']['name'] ?? null,
            'photographer_username' => $data['user']['username'] ?? null,
            'photographer_url' => $data['user']['links']['html'] ?? null,
            'download_location' => $data['links']['download_location'] ?? null,
            'exif' => $data['exif'] ?? null,
            'location' => $data['location'] ?? null,
            'tags' => $data['tags'] ?? null,
        ]);
    }

    /**
     * Find or create from Unsplash API response
     */
    public static function findOrCreateFromApi(array $data): self
    {
        return self::firstOrCreate(
            ['unsplash_id' => $data['id']],
            [
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'alt_description' => $data['alt_description'] ?? null,
                'urls' => $data['urls'] ?? [],
                'links' => $data['links'] ?? [],
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'color' => $data['color'] ?? null,
                'likes' => $data['likes'] ?? 0,
                'liked_by_user' => $data['liked_by_user'] ?? false,
                'photographer_name' => $data['user']['name'] ?? null,
                'photographer_username' => $data['user']['username'] ?? null,
                'photographer_url' => $data['user']['links']['html'] ?? null,
                'download_location' => $data['links']['download_location'] ?? null,
                'exif' => $data['exif'] ?? null,
                'location' => $data['location'] ?? null,
                'tags' => $data['tags'] ?? null,
            ],
        );
    }
}
