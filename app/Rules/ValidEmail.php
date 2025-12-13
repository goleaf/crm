<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Enhanced email validation rule for CRM modules.
 * Validates email format and checks for common issues.
 */
final readonly class ValidEmail implements ValidationRule
{
    public function __construct(
        private bool $allowMultiple = false,
        private bool $checkDisposable = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail(__('validation.email', ['attribute' => $attribute]));
            return;
        }

        $emails = $this->allowMultiple ? $this->parseMultipleEmails($value) : [$value];

        foreach ($emails as $email) {
            if (! $this->isValidEmail($email)) {
                $fail(__('validation.email', ['attribute' => $attribute]));
                return;
            }

            if ($this->checkDisposable && $this->isDisposableEmail($email)) {
                $fail(__('validation.custom.email.no_disposable', ['attribute' => $attribute]));
                return;
            }
        }
    }

    /**
     * @return array<string>
     */
    private function parseMultipleEmails(string $value): array
    {
        return array_filter(
            array_map(
                'trim',
                preg_split('/[,;]/', $value) ?: []
            ),
            fn (string $email): bool => $email !== ''
        );
    }

    private function isValidEmail(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Additional checks
        if (strlen($email) > 254) {
            return false;
        }

        // Check for consecutive dots
        if (str_contains($email, '..')) {
            return false;
        }

        // Check for valid domain
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        [$local, $domain] = $parts;

        // Local part validation
        if (strlen($local) > 64 || strlen($local) === 0) {
            return false;
        }

        // Domain validation
        if (strlen($domain) > 253 || strlen($domain) === 0) {
            return false;
        }

        return true;
    }

    private function isDisposableEmail(string $email): bool
    {
        $domain = Str::after($email, '@');
        
        // Common disposable email domains
        $disposableDomains = [
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'tempmail.org',
            'throwaway.email',
            'yopmail.com',
        ];

        return in_array(strtolower($domain), $disposableDomains, true);
    }
}