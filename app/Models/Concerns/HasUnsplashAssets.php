<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasUnsplashAssets
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<\App\Models\UnsplashAsset, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function unsplashAssets(): MorphToMany
    {
        return $this->morphToMany(UnsplashAsset::class, 'unsplashable', config('unsplash.tables.pivot', 'unsplashables'))
            ->withPivot(['collection', 'order', 'metadata'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function unsplashAssetsInCollection(string $collection = 'default'): MorphToMany
    {
        return $this->unsplashAssets()->wherePivot('collection', $collection);
    }

    public function attachUnsplashAsset(UnsplashAsset $asset, string $collection = 'default', int $order = 0, array $metadata = []): void
    {
        $this->unsplashAssets()->attach($asset->id, [
            'collection' => $collection,
            'order' => $order,
            'metadata' => json_encode($metadata),
        ]);
    }

    public function detachUnsplashAsset(UnsplashAsset $asset, string $collection = 'default'): void
    {
        $this->unsplashAssets()
            ->wherePivot('collection', $collection)
            ->detach($asset->id);
    }

    public function syncUnsplashAssets(array $ids, string $collection = 'default'): void
    {
        // This is a bit complex because sync() wipes everything.
        // To sync only a collection, we need to do it manually or use a more complex sync.
        // For simplicity here, we assume the user handles the collection logic or we implement basic sync.

        // Naive implementation: detach all for collection, then attach.
        $this->unsplashAssets()->wherePivot('collection', $collection)->detach();

        foreach ($ids as $index => $id) {
            $this->unsplashAssets()->attach($id, [
                'collection' => $collection,
                'order' => $index,
            ]);
        }
    }

    public function firstUnsplashAsset(string $collection = 'default'): ?UnsplashAsset
    {
        return $this->unsplashAssetsInCollection($collection)->first();
    }

    public function hasUnsplashAssets(string $collection = 'default'): bool
    {
        return $this->unsplashAssetsInCollection($collection)->exists();
    }
}
