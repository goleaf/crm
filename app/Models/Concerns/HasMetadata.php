<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ModelMeta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Trait for adding flexible JSON metadata support to Eloquent models.
 *
 * This trait provides a fluent interface for managing metadata on models,
 * allowing you to store arbitrary key-value pairs without modifying the schema.
 *
 * @property Collection<int, ModelMeta> $metaData
 * @property array<string, mixed> $defaultMetaValues
 */
trait HasMetadata
{
    /**
     * Boot the trait.
     */
    protected static function bootHasMetadata(): void
    {
        static::saved(function (self $model): void {
            $model->saveMeta();
        });

        static::deleted(function (self $model): void {
            $model->metas()->delete();
        });
    }

    /**
     * Initialize the trait.
     */
    protected function initializeHasMetadata(): void
    {
        $this->append('metaData');
    }

    /**
     * Relationship to metadata entries.
     *
     * @return MorphMany<ModelMeta, $this>
     */
    public function metas(): MorphMany
    {
        return $this->morphMany(ModelMeta::class, 'metable');
    }

    /**
     * Get the metadata collection.
     *
     * @return Collection<int, ModelMeta>
     */
    protected function getMetaDataAttribute(): Collection
    {
        if (! $this->relationLoaded('metas')) {
            $this->load('metas');
        }

        return $this->getRelation('metas')->keyBy('key');
    }

    /**
     * Set metadata value(s).
     *
     * @param  string|array<string, mixed>  $key
     */
    public function setMeta(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            return $this->setMetaArray($key);
        }

        return $this->setMetaString($key, $value);
    }

    /**
     * Set a single metadata value.
     */
    protected function setMetaString(string $key, mixed $value): self
    {
        $key = strtolower($key);

        // Check if this is a default value - if so, unset instead
        if ($this->isDefaultMetaValue($key, $value)) {
            return $this->unsetMeta($key);
        }

        if ($this->metaData->has($key)) {
            $this->metaData[$key]->markForDeletion(false);
            $this->metaData[$key]->value = $value;
        } else {
            $meta = new ModelMeta([
                'key' => $key,
                'value' => $value,
            ]);

            $this->metaData[$key] = $meta;
        }

        return $this;
    }

    /**
     * Set multiple metadata values.
     *
     * @param  array<string, mixed>  $metas
     */
    protected function setMetaArray(array $metas): self
    {
        foreach ($metas as $key => $value) {
            $this->setMetaString($key, $value);
        }

        return $this;
    }

    /**
     * Unset metadata value(s).
     *
     * @param  string|list<string>  $key
     */
    public function unsetMeta(string|array $key): self
    {
        if (is_array($key)) {
            return $this->unsetMetaArray($key);
        }

        return $this->unsetMetaString($key);
    }

    /**
     * Unset a single metadata value.
     */
    protected function unsetMetaString(string $key): self
    {
        $key = strtolower($key);

        if ($this->metaData->has($key)) {
            $this->metaData[$key]->markForDeletion();
        }

        return $this;
    }

    /**
     * Unset multiple metadata values.
     *
     * @param  list<string>  $keys
     */
    protected function unsetMetaArray(array $keys): self
    {
        foreach ($keys as $key) {
            $this->unsetMetaString($key);
        }

        return $this;
    }

    /**
     * Get metadata value(s).
     *
     * @param  string|list<string>|null  $key
     * @return mixed|BaseCollection<string, mixed>
     */
    public function getMeta(string|array|null $key = null, bool $raw = false): mixed
    {
        // Handle comma/pipe separated keys
        if (is_string($key) && preg_match('/[,|]/is', $key)) {
            $key = preg_split('/ ?[,|] ?/', $key);
        }

        return match (true) {
            is_string($key) => $this->getMetaString($key, $raw),
            is_array($key) => $this->getMetaArray($key, $raw),
            default => $this->getMetaNull($raw),
        };
    }

    /**
     * Get a single metadata value.
     */
    protected function getMetaString(string $key, bool $raw = false): mixed
    {
        $key = strtolower($key);
        $meta = $this->metaData->get($key);

        if ($meta === null || $meta->isMarkedForDeletion()) {
            return $this->getMetaDefaultValue($key);
        }

        return $raw ? $meta : $meta->value;
    }

    /**
     * Get multiple metadata values.
     *
     * @param  list<string>  $keys
     * @return BaseCollection<string, mixed>
     */
    protected function getMetaArray(array $keys, bool $raw = false): BaseCollection
    {
        $collection = new BaseCollection;

        foreach ($this->metaData as $meta) {
            if (! $meta->isMarkedForDeletion() && in_array($meta->key, $keys, true)) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    /**
     * Get all metadata values.
     *
     * @return BaseCollection<string, mixed>
     */
    protected function getMetaNull(bool $raw = false): BaseCollection
    {
        $collection = new BaseCollection;

        foreach ($this->metaData as $meta) {
            if (! $meta->isMarkedForDeletion()) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    /**
     * Check if metadata key exists.
     */
    public function hasMeta(string $key): bool
    {
        $key = strtolower($key);
        $meta = $this->metaData->get($key);

        return $meta !== null && ! $meta->isMarkedForDeletion();
    }

    /**
     * Save all metadata changes.
     */
    protected function saveMeta(): void
    {
        if (! $this->exists) {
            return;
        }

        foreach ($this->metaData as $meta) {
            if ($meta->isMarkedForDeletion()) {
                if ($meta->exists) {
                    $meta->delete();
                }
            } else {
                $meta->metable()->associate($this);
                $meta->save();
            }
        }
    }

    /**
     * Get the default value for a metadata key.
     */
    protected function getMetaDefaultValue(string $key): mixed
    {
        if (isset($this->defaultMetaValues) && array_key_exists($key, $this->defaultMetaValues)) {
            return $this->defaultMetaValues[$key];
        }

        return null;
    }

    /**
     * Check if a value matches the default for a key.
     */
    protected function isDefaultMetaValue(string $key, mixed $value): bool
    {
        if (! isset($this->defaultMetaValues) || ! array_key_exists($key, $this->defaultMetaValues)) {
            return false;
        }

        return $this->defaultMetaValues[$key] === $value;
    }

    /**
     * Scope to filter by metadata.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    protected function scopeWhereMeta($query, string $key, mixed $value, ?string $alias = null): mixed
    {
        $alias ??= 'model_meta';

        return $query
            ->join("model_meta as {$alias}", function ($join) use ($alias): void {
                $join->on($this->getQualifiedKeyName(), '=', "{$alias}.metable_id")
                    ->where("{$alias}.metable_type", '=', $this->getMorphClass());
            })
            ->where("{$alias}.key", '=', strtolower($key))
            ->where("{$alias}.value", '=', $value)
            ->select($this->getTable().'.*');
    }

    /**
     * Scope to join metadata table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    protected function scopeMeta($query, ?string $alias = null): mixed
    {
        $alias ??= 'model_meta';

        return $query
            ->join("model_meta as {$alias}", function ($join) use ($alias): void {
                $join->on($this->getQualifiedKeyName(), '=', "{$alias}.metable_id")
                    ->where("{$alias}.metable_type", '=', $this->getMorphClass());
            })
            ->select($this->getTable().'.*');
    }
}
