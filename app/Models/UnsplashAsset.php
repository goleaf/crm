<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class UnsplashAsset extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'urls' => 'array',
        'links' => 'array',
        'user' => 'array',
        'promoted_at' => 'datetime',
        'width' => 'integer',
        'height' => 'integer',
        'likes' => 'integer',
    ];

    public function getTable()
    {
        return config('unsplash.tables.assets', parent::getTable());
    }

    /**
     * Create or update an asset from Unsplash API data.
     */
    public static function findOrCreateFromApi(array $data): self
    {
        return self::updateOrCreate(
            ['unsplash_id' => $data['id']],
            [
                'description' => $data['description'] ?? $data['alt_description'],
                'alt_description' => $data['alt_description'],
                'urls' => $data['urls'],
                'links' => $data['links'],
                'user' => $data['user'],
                'width' => $data['width'],
                'height' => $data['height'],
                'color' => $data['color'] ?? null,
                'blur_hash' => $data['blur_hash'] ?? null,
                'likes' => $data['likes'] ?? 0,
                'promoted_at' => $data['promoted_at'] ?? null,
                'download_location' => $data['links']['download_location'] ?? null,
            ]
        );
    }

    /**
     * Get the image URL, preferring local if available.
     */
    public function getUrl(string $size = 'regular'): ?string
    {
        if ($this->isDownloaded()) {
            return $this->getLocalUrl();
        }

        return $this->urls[$size] ?? $this->urls['regular'] ?? null;
    }

    public function getLocalUrl(): ?string
    {
        if (! $this->local_path) {
            return null;
        }

        return Storage::disk($this->local_disk ?? config('unsplash.storage.disk', 'public'))
            ->url($this->local_path);
    }

    public function isDownloaded(): bool
    {
        return ! empty($this->local_path);
    }

    public function getAttributionHtml(): string
    {
        $utm = config('unsplash.utm_source', 'Laravel');
        $userCms = "?utm_source={$utm}&utm_medium=referral";

        $name = $this->user['name'];
        $userLink = $this->user['links']['html'].$userCms;
        $unsplashLink = "https://unsplash.com/{$userCms}";

        return sprintf(
            'Photo by <a href="%s" target="_blank" rel="noopener noreferrer">%s</a> on <a href="%s" target="_blank" rel="noopener noreferrer">Unsplash</a>',
            $userLink,
            $name,
            $unsplashLink
        );
    }

    public function getAttributionText(): string
    {
        return sprintf('Photo by %s on Unsplash', $this->user['name']);
    }
}
