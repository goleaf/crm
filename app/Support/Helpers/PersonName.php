<?php

declare(strict_types=1);

namespace App\Support\Helpers;

final class PersonName
{
    public static function format(?string $first, ?string $last = null, ?string $prefix = null, ?string $suffix = null, string $fallback = 'Unknown'): string
    {
        $parts = collect([
            trim((string) $prefix),
            trim((string) $first),
            trim((string) $last),
        ])->filter();

        $name = $parts->implode(' ');
        $suffix = trim((string) $suffix);

        if ($suffix !== '' && $suffix !== '0') {
            $name = $name === '' ? $suffix : "{$name}, {$suffix}";
        }

        return $name === '' ? $fallback : $name;
    }

    /**
     * Get initials from a name. Example: ("Ada", "Lovelace") => "AL".
     */
    public static function initials(?string $first, ?string $last = null): string
    {
        $letters = collect([$first, $last])
            ->filter(fn (?string $part): bool => filled($part))
            ->map(fn (?string $part): string => mb_substr(trim((string) $part), 0, 1))
            ->implode('');

        return mb_strtoupper($letters);
    }
}
