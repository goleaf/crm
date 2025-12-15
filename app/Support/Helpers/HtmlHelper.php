<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Support\HtmlString;

final class HtmlHelper
{
    /**
     * Create a safe HTML string.
     */
    public static function safe(string $html): HtmlString
    {
        return new HtmlString($html);
    }

    /**
     * Strip all HTML tags from a string.
     */
    public static function stripTags(string $html, ?array $allowedTags = null): string
    {
        if ($allowedTags === null) {
            return strip_tags($html);
        }

        $allowed = '<' . implode('><', $allowedTags) . '>';

        return strip_tags($html, $allowed);
    }

    /**
     * Convert line breaks to <br> tags.
     */
    public static function nl2br(string $text): HtmlString
    {
        return new HtmlString(nl2br(e($text), false));
    }

    /**
     * Create a link with optional attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public static function link(string $url, string $text, array $attributes = []): HtmlString
    {
        $attrs = self::buildAttributes($attributes);

        return new HtmlString(sprintf('<a href="%s"%s>%s</a>', e($url), $attrs, e($text)));
    }

    /**
     * Create an external link (opens in new tab).
     *
     * @param array<string, mixed> $attributes
     */
    public static function externalLink(string $url, string $text, array $attributes = []): HtmlString
    {
        $attributes['target'] = '_blank';
        $attributes['rel'] = 'noopener noreferrer';

        return self::link($url, $text, $attributes);
    }

    /**
     * Create an image tag.
     *
     * @param array<string, mixed> $attributes
     */
    public static function image(string $src, string $alt = '', array $attributes = []): HtmlString
    {
        $attributes['alt'] = $alt;
        $attrs = self::buildAttributes($attributes);

        return new HtmlString(sprintf('<img src="%s"%s>', e($src), $attrs));
    }

    /**
     * Create a mailto link.
     *
     * @param array<string, mixed> $attributes
     */
    public static function mailto(string $email, ?string $text = null, array $attributes = []): HtmlString
    {
        $text ??= $email;

        return self::link('mailto:' . $email, $text, $attributes);
    }

    /**
     * Create a tel link.
     *
     * @param array<string, mixed> $attributes
     */
    public static function tel(string $phone, ?string $text = null, array $attributes = []): HtmlString
    {
        $text ??= $phone;

        return self::link('tel:' . $phone, $text, $attributes);
    }

    /**
     * Truncate HTML while preserving tags.
     */
    public static function truncate(string $html, int $length = 100, string $end = '...'): string
    {
        $plainText = strip_tags($html);

        if (strlen($plainText) <= $length) {
            return $html;
        }

        return StringHelper::limit($plainText, $length, $end);
    }

    /**
     * Build HTML attributes string.
     *
     * @param array<string, mixed> $attributes
     */
    private static function buildAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $html = [];

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === false) {
                continue;
            }
            if ($value === true) {
                $html[] = e($key);

                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            $html[] = sprintf('%s="%s"', e($key), e($value));
        }

        return $html !== [] ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Sanitize HTML to prevent XSS.
     */
    public static function sanitize(string $html, ?array $allowedTags = null): string
    {
        $allowedTags ??= ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li'];

        // Strip all tags except allowed
        $html = self::stripTags($html, $allowedTags);

        // Remove dangerous attributes
        $html = preg_replace('/<(\w+)[^>]*\s(on\w+|style|javascript:)[^>]*>/i', '<$1>', $html) ?? $html;

        return $html;
    }

    /**
     * Convert URLs in text to clickable links.
     */
    public static function linkify(string $text): HtmlString
    {
        $pattern = '/(https?:\/\/[^\s]+)/i';
        $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';

        $linked = preg_replace($pattern, $replacement, e($text)) ?? e($text);

        return new HtmlString($linked);
    }

    /**
     * Create a badge/tag element.
     */
    public static function badge(string $text, string $color = 'gray'): HtmlString
    {
        $classes = match ($color) {
            'primary' => 'bg-blue-100 text-blue-800',
            'success' => 'bg-green-100 text-green-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'danger' => 'bg-red-100 text-red-800',
            'info' => 'bg-cyan-100 text-cyan-800',
            default => 'bg-gray-100 text-gray-800',
        };

        return new HtmlString(sprintf(
            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
            $classes,
            e($text),
        ));
    }

    /**
     * Create an avatar with initials.
     */
    public static function avatar(string $name, int $size = 40, ?string $color = null): HtmlString
    {
        $initials = StringHelper::initials($name, 2);
        $color ??= ColorHelper::random();
        $textColor = ColorHelper::contrastText($color);

        return new HtmlString(sprintf(
            '<div class="inline-flex items-center justify-center rounded-full font-semibold" style="width: %dpx; height: %dpx; background-color: %s; color: %s;">%s</div>',
            $size,
            $size,
            $color,
            $textColor,
            e($initials),
        ));
    }
}
