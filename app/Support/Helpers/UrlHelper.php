<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class UrlHelper
{
    /**
     * Check if a URL is external.
     */
    public static function isExternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host !== null && $host !== $appHost;
    }

    /**
     * Add query parameters to a URL.
     *
     * @param array<string, mixed> $params
     */
    public static function addQuery(string $url, array $params): string
    {
        $parsed = parse_url($url);
        $query = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $query = array_merge($query, $params);

        $newQuery = http_build_query($query);

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . $host . $port . $path . ($newQuery !== '' && $newQuery !== '0' ? '?' . $newQuery : '') . $fragment;
    }

    /**
     * Generate a signed URL that expires.
     */
    public static function signedRoute(
        string $name,
        array $parameters = [],
        \DateTimeInterface|\DateInterval|int|null $expiration = null,
    ): string {
        if ($expiration !== null) {
            return URL::temporarySignedRoute($name, $expiration, $parameters);
        }

        return URL::signedRoute($name, $parameters);
    }

    /**
     * Sanitize a URL for safe display.
     */
    public static function sanitize(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }

    /**
     * Validate if a string is a valid URL.
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Extract domain from URL.
     */
    public static function domain(string $url): ?string
    {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * Build a URL with UTM parameters for tracking.
     *
     * @param array<string, string> $utm
     */
    public static function withUtm(string $url, array $utm): string
    {
        $params = [];

        if (isset($utm['source'])) {
            $params['utm_source'] = $utm['source'];
        }

        if (isset($utm['medium'])) {
            $params['utm_medium'] = $utm['medium'];
        }

        if (isset($utm['campaign'])) {
            $params['utm_campaign'] = $utm['campaign'];
        }

        if (isset($utm['term'])) {
            $params['utm_term'] = $utm['term'];
        }

        if (isset($utm['content'])) {
            $params['utm_content'] = $utm['content'];
        }

        return self::addQuery($url, $params);
    }

    /**
     * Shorten a URL for display (e.g., "https://example.com/very/long/path" -> "example.com/...").
     */
    public static function shorten(string $url, int $maxLength = 50): string
    {
        if (strlen($url) <= $maxLength) {
            return $url;
        }

        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';

        if (strlen($domain) >= $maxLength) {
            return Str::limit($domain, $maxLength);
        }

        $remaining = $maxLength - strlen($domain) - 4; // 4 for "..." and "/"

        if ($remaining > 0 && $path !== '') {
            return $domain . '/' . Str::limit(ltrim($path, '/'), $remaining);
        }

        return $domain;
    }
}
