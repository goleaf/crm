<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use JonPurvis\Squeaky\Enums\Locale;
use JonPurvis\Squeaky\Rules\Clean;

final class CleanContent extends Clean
{
    public function __construct(?array $locales = null)
    {
        parent::__construct($this->resolveLocales($locales));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        parent::validate($attribute, (string) $value, $fail);
    }

    private function resolveLocales(?array $locales): array
    {
        $configured = $locales ?? config('squeaky.locales', []);
        $fallback = config('squeaky.fallback_locale');

        if ($fallback !== null) {
            $configured[] = $fallback;
        }

        $resolved = array_values(array_unique(array_filter(array_map(
            static fn (string|Locale|null $locale): ?string => match (true) {
                $locale instanceof Locale => $locale->value,
                is_string($locale) => $locale,
                default => null,
            },
            $configured,
        ))));

        if ($resolved === []) {
            $resolved[] = config('app.locale', 'en');
        }

        return $resolved;
    }
}
