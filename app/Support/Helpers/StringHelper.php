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

    /**
     * Truncate a string to a specified length.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return Str::limit($value, $limit, $end);
    }

    /**
     * Truncate a string by word count.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        return Str::words($value, $words, $end);
    }

    /**
     * Convert a string to title case.
     */
    public static function title(string $value): string
    {
        return Str::title($value);
    }

    /**
     * Convert a string to camelCase.
     */
    public static function camel(string $value): string
    {
        return Str::camel($value);
    }

    /**
     * Convert a string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        return Str::snake($value, $delimiter);
    }

    /**
     * Convert a string to kebab-case.
     */
    public static function kebab(string $value): string
    {
        return Str::kebab($value);
    }

    /**
     * Convert a string to StudlyCase.
     */
    public static function studly(string $value): string
    {
        return Str::studly($value);
    }

    /**
     * Get the plural form of a word.
     */
    public static function plural(string $value, int|array|\Countable $count = 2): string
    {
        return Str::plural($value, $count);
    }

    /**
     * Get the singular form of a word.
     */
    public static function singular(string $value): string
    {
        return Str::singular($value);
    }

    /**
     * Generate a random string.
     */
    public static function random(int $length = 16): string
    {
        return Str::random($length);
    }

    /**
     * Mask a portion of a string with a repeated character.
     */
    public static function mask(string $string, string $character = '*', int $index = 0, ?int $length = null): string
    {
        return Str::mask($string, $character, $index, $length);
    }

    /**
     * Remove all whitespace from a string.
     */
    public static function removeWhitespace(string $value): string
    {
        return preg_replace('/\s+/', '', $value) ?? $value;
    }

    /**
     * Check if a string contains any of the given values.
     */
    public static function containsAny(string $haystack, array $needles, bool $ignoreCase = false): bool
    {
        return Str::containsAll($haystack, $needles, $ignoreCase);
    }

    /**
     * Extract initials from a name.
     */
    public static function initials(string $name, int $limit = 2): string
    {
        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, $limit) as $word) {
            if ($word !== '') {
                $initials .= strtoupper($word[0]);
            }
        }

        return $initials;
    }

    /**
     * Highlight search terms in text.
     */
    public static function highlight(string $text, string|array $search, string $class = 'highlight'): string
    {
        $searches = is_array($search) ? $search : [$search];

        foreach ($searches as $term) {
            if ($term === '') {
                continue;
            }

            $text = preg_replace(
                '/(' . preg_quote((string) $term, '/') . ')/i',
                '<mark class="' . $class . '">$1</mark>',
                $text,
            ) ?? $text;
        }

        return $text;
    }

    /**
     * Strip HTML tags and decode entities.
     */
    public static function plainText(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Excerpt text from HTML content.
     */
    public static function excerpt(string $html, int $length = 200): string
    {
        $plain = self::plainText($html);

        return self::limit($plain, $length);
    }
}
