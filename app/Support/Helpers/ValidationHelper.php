<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Validator;

final class ValidationHelper
{
    /**
     * Validate an email address.
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate a URL.
     */
    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate an IP address.
     */
    public static function isIp(string $ip, bool $allowPrivate = true): bool
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;

        if (! $allowPrivate) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }

    /**
     * Validate a phone number (basic format check).
     */
    public static function isPhone(string $phone): bool
    {
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)\+\.]/', '', $phone);

        // Check if it's numeric and has reasonable length
        return is_numeric($cleaned) && strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Validate a credit card number using Luhn algorithm.
     */
    public static function isCreditCard(string $number): bool
    {
        $number = preg_replace('/\D/', '', $number);

        if (strlen((string) $number) < 13 || strlen((string) $number) > 19) {
            return false;
        }

        $sum = 0;
        $length = strlen((string) $number);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * Validate a postal code for a specific country.
     */
    public static function isPostalCode(string $code, string $country = 'US'): bool
    {
        $patterns = [
            'US' => '/^\d{5}(-\d{4})?$/',
            'UK' => '/^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i',
            'CA' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i',
            'DE' => '/^\d{5}$/',
            'FR' => '/^\d{5}$/',
            'AU' => '/^\d{4}$/',
        ];

        $pattern = $patterns[strtoupper($country)] ?? null;

        if ($pattern === null) {
            return false;
        }

        return (bool) preg_match($pattern, $code);
    }

    /**
     * Validate a date string.
     */
    public static function isDate(string $date, string $format = 'Y-m-d'): bool
    {
        $validator = Validator::make(['date' => $date], [
            'date' => 'date_format:' . $format,
        ]);

        return ! $validator->fails();
    }

    /**
     * Validate a JSON string.
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate a UUID.
     */
    public static function isUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid,
        );
    }

    /**
     * Validate a slug (URL-friendly string).
     */
    public static function isSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Validate a hex color code.
     */
    public static function isHexColor(string $color): bool
    {
        return (bool) preg_match('/^#?([a-f0-9]{6}|[a-f0-9]{3})$/i', $color);
    }

    /**
     * Validate a MAC address.
     */
    public static function isMacAddress(string $mac): bool
    {
        return (bool) preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
    }

    /**
     * Validate a username (alphanumeric with underscores and hyphens).
     */
    public static function isUsername(string $username, int $minLength = 3, int $maxLength = 20): bool
    {
        $length = strlen($username);

        if ($length < $minLength || $length > $maxLength) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $username);
    }

    /**
     * Validate password strength.
     *
     * @return array{valid: bool, score: int, feedback: array<int, string>}
     */
    public static function validatePasswordStrength(
        string $password,
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true,
    ): array {
        $feedback = [];
        $score = 0;

        // Length check
        if (strlen($password) < $minLength) {
            $feedback[] = "Password must be at least {$minLength} characters long";
        } else {
            $score += 20;
        }

        // Uppercase check
        if ($requireUppercase && ! preg_match('/[A-Z]/', $password)) {
            $feedback[] = 'Password must contain at least one uppercase letter';
        } else {
            $score += 20;
        }

        // Lowercase check
        if ($requireLowercase && ! preg_match('/[a-z]/', $password)) {
            $feedback[] = 'Password must contain at least one lowercase letter';
        } else {
            $score += 20;
        }

        // Numbers check
        if ($requireNumbers && ! preg_match('/\d/', $password)) {
            $feedback[] = 'Password must contain at least one number';
        } else {
            $score += 20;
        }

        // Special characters check
        if ($requireSpecialChars && ! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $feedback[] = 'Password must contain at least one special character';
        } else {
            $score += 20;
        }

        return [
            'valid' => $feedback === [],
            'score' => $score,
            'feedback' => $feedback,
        ];
    }
}
