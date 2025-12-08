<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class StringHelper
{
    /**
     * Wrap a string at the given length with optional HTML-friendly breaks.
     */
    public static function wordWrap(
        mixed $value,
        int $characters = 80,
        string $break = PHP_EOL,
        bool $cutLongWords = false,
        ?string $emptyPlaceholder = '—',
        bool $escape = true,
    ): HtmlString|string|null {
        if ($value === null) {
            return $emptyPlaceholder;
        }

        if ($value instanceof Htmlable) {
            $escape = false;
        }

        $stringValue = is_string($value)
            ? $value
            : ((is_scalar($value) || (is_object($value) && method_exists($value, '__toString')))
                ? (string) $value
                : null);

        if ($stringValue === null) {
            return $emptyPlaceholder;
        }

        $normalized = $escape ? e($stringValue) : $stringValue;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return $emptyPlaceholder;
        }

        $wrapped = Str::wordWrap(
            string: $normalized,
            characters: $characters,
            break: $break,
            cutLongWords: $cutLongWords,
        );

        if (str_contains($break, '<')) {
            return new HtmlString($wrapped);
        }

        return $wrapped;
    }
}
