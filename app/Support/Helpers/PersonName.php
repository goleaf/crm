<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use App\Support\PersonNameFormatter;
use HosmelQ\NameOfPerson\PersonName as NameOfPerson;

final class PersonName
{
    public static function format(?string $first, ?string $last = null, ?string $prefix = null, ?string $suffix = null, string $fallback = 'Unknown'): string
    {
        $personName = PersonNameFormatter::make(
            collect([$prefix, $first, $last])
                ->map(fn (?string $value): string => trim((string) $value))
                ->filter()
                ->implode(' ')
        );

        $suffix = trim((string) $suffix);

        if (! $personName instanceof NameOfPerson) {
            return $suffix !== '' && $suffix !== '0'
                ? "{$fallback}, {$suffix}"
                : $fallback;
        }

        $fullName = $personName->full();

        return $suffix !== '' && $suffix !== '0'
            ? "{$fullName}, {$suffix}"
            : $fullName;
    }

    /**
     * Get initials from a name. Example: ("Ada", "Lovelace") => "AL".
     */
    public static function initials(?string $first, ?string $last = null): string
    {
        return PersonNameFormatter::initials(
            collect([$first, $last])->filter()->implode(' '),
            2,
            ''
        );
    }
}
