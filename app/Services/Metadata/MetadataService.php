<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use App\Models\Concerns\HasMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service for managing model metadata operations.
 *
 * Provides a centralized interface for working with flexible JSON metadata
 * on Eloquent models using the HasMetadata trait.
 */
final readonly class MetadataService
{
    /**
     * Set metadata on a model.
     *
     * @param Model&HasMetadata           $model
     * @param string|array<string, mixed> $key
     */
    public function set(Model $model, string|array $key, mixed $value = null): Model
    {
        $this->ensureHasMetadata($model);

        $model->setMeta($key, $value);
        $model->save();

        return $model;
    }

    /**
     * Get metadata from a model.
     *
     * @param Model&HasMetadata        $model
     * @param string|list<string>|null $key
     *
     * @return mixed|Collection<string, mixed>
     */
    public function get(Model $model, string|array|null $key = null, bool $raw = false): mixed
    {
        $this->ensureHasMetadata($model);

        return $model->getMeta($key, $raw);
    }

    /**
     * Remove metadata from a model.
     *
     * @param Model&HasMetadata   $model
     * @param string|list<string> $key
     */
    public function remove(Model $model, string|array $key): Model
    {
        $this->ensureHasMetadata($model);

        $model->unsetMeta($key);
        $model->save();

        return $model;
    }

    /**
     * Check if model has specific metadata.
     *
     * @param Model&HasMetadata $model
     */
    public function has(Model $model, string $key): bool
    {
        $this->ensureHasMetadata($model);

        return $model->hasMeta($key);
    }

    /**
     * Get all metadata from a model.
     *
     * @param Model&HasMetadata $model
     *
     * @return Collection<string, mixed>
     */
    public function all(Model $model): Collection
    {
        $this->ensureHasMetadata($model);

        return $model->getMeta();
    }

    /**
     * Bulk set metadata on a model.
     *
     * @param Model&HasMetadata    $model
     * @param array<string, mixed> $metadata
     */
    public function bulkSet(Model $model, array $metadata): Model
    {
        $this->ensureHasMetadata($model);

        $model->setMeta($metadata);
        $model->save();

        return $model;
    }

    /**
     * Bulk remove metadata from a model.
     *
     * @param Model&HasMetadata $model
     * @param list<string>      $keys
     */
    public function bulkRemove(Model $model, array $keys): Model
    {
        $this->ensureHasMetadata($model);

        $model->unsetMeta($keys);
        $model->save();

        return $model;
    }

    /**
     * Sync metadata on a model (replace all existing metadata).
     *
     * @param Model&HasMetadata    $model
     * @param array<string, mixed> $metadata
     */
    public function sync(Model $model, array $metadata): Model
    {
        $this->ensureHasMetadata($model);

        // Get all existing keys
        $existingKeys = $model->getMeta()->keys()->all();

        // Remove all existing metadata
        if ($existingKeys !== []) {
            $model->unsetMeta($existingKeys);
        }

        // Set new metadata
        $model->setMeta($metadata);
        $model->save();

        return $model;
    }

    /**
     * Merge metadata with existing values.
     *
     * @param Model&HasMetadata    $model
     * @param array<string, mixed> $metadata
     */
    public function merge(Model $model, array $metadata): Model
    {
        $this->ensureHasMetadata($model);

        $existing = $model->getMeta()->all();
        $merged = array_merge($existing, $metadata);

        $model->setMeta($merged);
        $model->save();

        return $model;
    }

    /**
     * Get metadata with default value if not exists.
     *
     * @param Model&HasMetadata $model
     */
    public function getWithDefault(Model $model, string $key, mixed $default): mixed
    {
        $this->ensureHasMetadata($model);

        $value = $model->getMeta($key);

        return $value ?? $default;
    }

    /**
     * Increment a numeric metadata value.
     *
     * @param Model&HasMetadata $model
     */
    public function increment(Model $model, string $key, int|float $amount = 1): Model
    {
        $this->ensureHasMetadata($model);

        $current = $model->getMeta($key) ?? 0;
        $new = is_numeric($current) ? $current + $amount : $amount;

        $model->setMeta($key, $new);
        $model->save();

        return $model;
    }

    /**
     * Decrement a numeric metadata value.
     *
     * @param Model&HasMetadata $model
     */
    public function decrement(Model $model, string $key, int|float $amount = 1): Model
    {
        $this->ensureHasMetadata($model);

        $current = $model->getMeta($key) ?? 0;
        $new = is_numeric($current) ? $current - $amount : -$amount;

        $model->setMeta($key, $new);
        $model->save();

        return $model;
    }

    /**
     * Toggle a boolean metadata value.
     *
     * @param Model&HasMetadata $model
     */
    public function toggle(Model $model, string $key): Model
    {
        $this->ensureHasMetadata($model);

        $current = $model->getMeta($key) ?? false;
        $new = ! (bool) $current;

        $model->setMeta($key, $new);
        $model->save();

        return $model;
    }

    /**
     * Ensure the model uses the HasMetadata trait.
     *
     * @param Model&HasMetadata $model
     *
     * @throws \InvalidArgumentException
     */
    private function ensureHasMetadata(Model $model): void
    {
        if (! in_array(HasMetadata::class, class_uses_recursive($model), true)) {
            throw new \InvalidArgumentException(
                sprintf('Model %s must use the HasMetadata trait', $model::class),
            );
        }
    }
}
