<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Phone number validation rule for CRM modules.
 * Validates phone number format and structure.
 */
final readonly class ValidPhone implements ValidationRule
{
    public function __construct(
        private ?string $countryCode = null,
        private bool $allowInternational = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail(__('validation.custom.phone.invalid_format', ['attribute' => $attribute]));
            return;
        }

        $phone = $this->normalizePhone($value);

        if (! $this->isValidPhone($phone)) {
            $fail(__('validation.custom.phone.invalid_format', ['attribute' => $attribute]));
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters except + at the beginning
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure + is only at the beginning
        if (str_contains($normalized, '+')) {
            $normalized = '+' . str_replace('+', '', $normalized);
        }

        return $normalized ?? '';
    }

    private function isValidPhone(string $phone): bool
    {
        // Empty after normalization
        if ($phone === '') {
            return false;
        }

        // Check length constraints
        $digits = str_replace('+', '', $phone);
        $digitCount = strlen($digits);

        // Too short or too long
        if ($digitCount < 7 || $digitCount > 15) {
            return false;
        }

        // International format validation
        if (str_starts_with($phone, '+')) {
            if (! $this->allowInternational) {
                return false;
            }
            
            // Must have country code (1-4 digits) + number
            return $digitCount >= 8 && $digitCount <= 15;
        }

        // Domestic format validation
        if ($this->countryCode !== null) {
            return $this->validateDomesticFormat($phone, $this->countryCode);
        }

        // Generic validation - reasonable length
        return $digitCount >= 7 && $digitCount <= 12;
    }

    private function validateDomesticFormat(string $phone, string $countryCode): bool
    {
        $digits = strlen($phone);

        return match (strtoupper($countryCode)) {
            'US', 'CA' => $digits === 10, // North American Numbering Plan
            'GB' => $digits >= 10 && $digits <= 11,
            'DE' => $digits >= 11 && $digits <= 12,
            'FR' => $digits === 10,
            'AU' => $digits === 9 || $digits === 10,
            default => $digits >= 7 && $digits <= 12,
        };
    }
}