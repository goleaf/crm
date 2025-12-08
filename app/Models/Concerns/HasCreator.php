<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\CreationSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property-read string $created_by
 * @property-read User|null $editor
 */
trait HasCreator
{
    use Userstamps {
        creator as userstampsCreator;
        editor as userstampsEditor;
        destroyer as userstampsDestroyer;
    }

    public const CREATED_BY = 'creator_id';

    public const UPDATED_BY = 'editor_id';

    public const DELETED_BY = 'deleted_by';

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->userstampsCreator();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->userstampsEditor();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function destroyer(): BelongsTo
    {
        return $this->userstampsDestroyer();
    }

    /**
     * Allow temporarily disabling userstamps.
     */
    protected function scopeWithoutUserstamps(Builder $query, callable $callback): mixed
    {
        $this->stopUserstamping();

        try {
            return $callback($query);
        } finally {
            $this->startUserstamping();
        }
    }

    /**
     * Determine if the system created the record.
     */
    public function isSystemCreated(): bool
    {
        return $this->creation_source === CreationSource::SYSTEM;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->creation_source === CreationSource::SYSTEM ?
                '⊙ System' :
                $this->creator->name ?? 'Unknown',
        );
    }
}
