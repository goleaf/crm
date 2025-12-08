<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasUnsplashAssets
{
    /**
     * Get all Unsplash assets for this model
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<\App\Models\UnsplashAsset, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function unsplashAssets(): MorphToMany
    {
        return $this->morphToMany(
            UnsplashAsset::class,
            'unsplashable',
            config('unsplash.tables.pivot', 'unsplashables'),
        )->withPivot(['collection', 'order', 'metadata'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Get Unsplash assets for a specific collection
     */
    public function unsplashAssetsInCollection(string $collection): MorphToMany
    {
        return $this->unsplashAssets()->wherePivot('collection', $collection);
    }

    /**
     * Attach an Unsplash asset
     */
    public function attachUnsplashAsset(
        UnsplashAsset $asset,
        ?string $collection = null,
        int $order = 0,
        array $metadata = [],
    ): void {
        $this->unsplashAssets()->attach($asset->id, [
            'collection' => $collection,
            'order' => $order,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Detach an Unsplash asset
     */
    public function detachUnsplashAsset(UnsplashAsset $asset, ?string $collection = null): void
    {
        $query = $this->unsplashAssets()->wherePivot('unsplash_asset_id', $asset->id);

        if ($collection) {
            $query->wherePivot('collection', $collection);
        }

        $query->detach();
    }

    /**
     * Sync Unsplash assets for a collection
     */
    public function syncUnsplashAssets(array $assetIds, ?string $collection = null): void
    {
        $syncData = [];

        foreach ($assetIds as $index => $assetId) {
            $syncData[$assetId] = [
                'collection' => $collection,
                'order' => $index,
                'metadata' => [],
            ];
        }

        if ($collection) {
            // Only sync assets in this collection
            $this->unsplashAssets()
                ->wherePivot('collection', $collection)
                ->sync($syncData);
        } else {
            $this->unsplashAssets()->sync($syncData);
        }
    }

    /**
     * Check if model has any Unsplash assets
     */
    public function hasUnsplashAssets(?string $collection = null): bool
    {
        $query = $this->unsplashAssets();

        if ($collection) {
            $query->wherePivot('collection', $collection);
        }

        return $query->exists();
    }

    /**
     * Get the first Unsplash asset
     */
    public function firstUnsplashAsset(?string $collection = null): ?UnsplashAsset
    {
        $query = $this->unsplashAssets();

        if ($collection) {
            $query->wherePivot('collection', $collection);
        }

        return $query->first();
    }
}
