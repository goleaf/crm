<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enhanced URL validation rule for CRM modules.
 * Validates URL format and checks for security issues.
 */
final readonly class ValidUrl implements ValidationRule
{
    public function __construct(
        private bool $requireHttps = false,
        private bool $allowLocalhost = true,
        private bool $allowIpAddresses = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        $url = $this->normalizeUrl($value);

        if (! $this->isValidUrl($url)) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        if ($this->requireHttps && ! str_starts_with($url, 'https://')) {
            $fail(__('validation.custom.url.https_required', ['attribute' => $attribute]));
            return;
        }

        if (! $this->allowLocalhost && $this->isLocalhost($url)) {
            $fail(__('validation.custom.url.no_localhost', ['attribute' => $attribute]));
            return;
        }

        if (! $this->allowIpAddresses && $this->isIpAddress($url)) {
            $fail(__('validation.custom.url.no_ip_address', ['attribute' => $attribute]));
        }
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // Add protocol if missing
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    private function isValidUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Check allowed schemes
        if (! in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }

        // Check host length
        if (strlen($parsed['host']) > 253) {
            return false;
        }

        return true;
    }

    private function isLocalhost(string $url): bool
    {
        $parsed = parse_url($url);
        
        if (! isset($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) ||
               str_ends_with($host, '.local') ||
               str_ends_with($host, '.localhost');
    }

    private function isIpAddress(string $url): bool
    {
        $parsed = parse_url($url);
        
        if (! isset($parsed['host'])) {
            return false;
        }

        return filter_var($parsed['host'], FILTER_VALIDATE_IP) !== false;
    }
}