<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Content\ProfanityFilterService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validation rule to check for profanity in text.
 *
 * Usage:
 * - new NoProfanity()
 * - new NoProfanity('spanish')
 * - new NoProfanity('all') // Check all languages
 * - new NoProfanity(logViolations: true)
 */
final readonly class NoProfanity implements ValidationRule
{
    public function __construct(
        private ?string $language = null,
        private bool $logViolations = true
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $service = resolve(ProfanityFilterService::class);

        $result = $this->language === 'all'
            ? $service->checkAllLanguages($value)
            : $service->validateAndClean($value, $this->language, $this->logViolations);

        if ($result['has_profanity'] ?? ! $result['valid']) {
            $fail(__('validation.no_profanity', ['attribute' => $attribute]));
        }
    }
}
