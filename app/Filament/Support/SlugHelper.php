<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class SlugHelper
{
    /**
     * Returns a reusable handler for syncing a slug field from a base text input.
     *
     * @param string        $slugField         The field name to update (defaults to `slug`).
     * @param bool          $allowReslugOnEdit Whether edits are allowed to update the slug when it still matches the previous base value.
     * @param callable|null $lockCondition     Optional callback receiving (?string $operation, ?Model $record): bool to block updates when true.
     */
    public static function updateSlug(
        string $slugField = 'slug',
        bool $allowReslugOnEdit = true,
        ?callable $lockCondition = null,
    ): \Closure {
        return static function (Get $get, Set $set, ?string $operation, ?string $old, ?string $state, ?Model $record) use ($slugField, $allowReslugOnEdit, $lockCondition): void {
            if (self::isLocked($operation, $record, $lockCondition)) {
                return;
            }

            $currentSlug = (string) ($get($slugField) ?? '');
            $oldSlugFromBase = $old === null ? '' : Str::slug($old);
            $newSlug = Str::slug((string) $state);
            $slugManuallyChanged = $currentSlug !== '' && $currentSlug !== $oldSlugFromBase;

            $shouldUpdate = ! $slugManuallyChanged
                && (
                    $operation === 'create'
                    || ($allowReslugOnEdit && ($currentSlug === '' || $currentSlug === $oldSlugFromBase))
                );

            if (! $shouldUpdate) {
                return;
            }

            $set($slugField, $newSlug);
        };
    }

    /**
     * Centralized check for locking slug updates/edits.
     */
    public static function isLocked(?string $operation, ?Model $record, ?callable $lockCondition = null): bool
    {
        return $lockCondition !== null && $lockCondition($operation, $record) === true;
    }
}
