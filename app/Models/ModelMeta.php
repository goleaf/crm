<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model for storing flexible metadata on any model.
 *
 * @property int $id
 * @property string $metable_type
 * @property int $metable_id
 * @property string $type
 * @property string $key
 * @property mixed $value
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class ModelMeta extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'metable_type',
        'metable_id',
        'type',
        'key',
        'value',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'null',
    ];

    /**
     * The table associated with the model.
     */
    protected $table = 'model_meta';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * Track if this meta entry is marked for deletion.
     */
    private bool $markedForDeletion = false;

    /**
     * Get the parent metable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function metable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark this meta entry for deletion.
     */
    public function markForDeletion(bool $mark = true): void
    {
        $this->markedForDeletion = $mark;
    }

    /**
     * Check if this meta entry is marked for deletion.
     */
    public function isMarkedForDeletion(): bool
    {
        return $this->markedForDeletion;
    }

    /**
     * Get the value attribute with automatic type casting.
     */
    protected function getValueAttribute(mixed $value): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'double', 'float' => (float) $value,
            'array', 'object' => json_decode((string) $value, true),
            'NULL' => null,
            default => $value,
        };
    }

    /**
     * Set the value attribute with automatic type detection.
     */
    protected function setValueAttribute(mixed $value): void
    {
        $this->attributes['type'] = gettype($value);

        $this->attributes['value'] = match (true) {
            is_array($value), is_object($value) => json_encode($value),
            is_bool($value) => $value ? '1' : '0',
            is_null($value) => null,
            default => (string) $value,
        };
    }
}
