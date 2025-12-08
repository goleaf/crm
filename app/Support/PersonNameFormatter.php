<?php

declare(strict_types=1);

namespace App\Support;

use HosmelQ\NameOfPerson\PersonName;

/**
 * Centralized helpers for parsing and formatting person names via name-of-person.
 */
final class PersonNameFormatter
{
    /**
     * Build a PersonName instance from either a PersonName or string value.
     */
    public static function make(null|string|PersonName $name): ?PersonName
    {
        if ($name instanceof PersonName) {
            return $name;
        }

        if (is_string($name) && mb_trim($name) !== '') {
            return PersonName::fromFull($name);
        }

        return null;
    }

    public static function full(null|string|PersonName $name, string $fallback = ''): string
    {
        $personName = self::make($name);

        if ($personName instanceof PersonName) {
            return $personName->full();
        }

        if (is_string($name) && mb_trim($name) !== '') {
            return mb_trim($name);
        }

        return $fallback;
    }

    public static function first(null|string|PersonName $name, string $fallback = ''): string
    {
        return self::make($name)?->first ?? $fallback;
    }

    public static function last(null|string|PersonName $name, string $fallback = ''): string
    {
        return self::make($name)?->last ?? $fallback;
    }

    public static function familiar(null|string|PersonName $name, string $fallback = ''): string
    {
        $personName = self::make($name);

        return $personName?->familiar() ?? $fallback;
    }

    public static function initials(null|string|PersonName $name, int $length = 2, string $fallback = '?'): string
    {
        $personName = self::make($name);
        $length = max(1, $length);

        if (! $personName instanceof PersonName) {
            return $fallback;
        }

        $initials = $personName->initials();

        if ($initials === '') {
            return $fallback;
        }

        return mb_substr($initials, 0, $length);
    }

    public static function mentionable(null|string|PersonName $name, string $fallback = ''): string
    {
        $personName = self::make($name);

        return $personName?->mentionable() ?? $fallback;
    }
}
