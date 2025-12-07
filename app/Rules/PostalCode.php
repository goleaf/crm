<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule as RuleContract;

final readonly class PostalCode implements RuleContract
{
    public function __construct(private string $countryCode) {}

    public function passes($attribute, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $patterns = config('address.postal_code_patterns', []);
        $pattern = $patterns[strtoupper($this->countryCode)] ?? null;

        if ($pattern === null) {
            return true;
        }

        return (bool) preg_match($pattern, (string) $value);
    }

    public function message(): string
    {
        return 'The :attribute format is invalid for the selected country.';
    }
}
