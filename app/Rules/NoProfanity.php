<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Content\ProfanityFilterService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class NoProfanity implements ValidationRule
{
    public function __construct(
        private string $language = 'english',
        private bool $logViolations = true
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        /** @var ProfanityFilterService $service */
        $service = resolve(ProfanityFilterService::class);

        $result = $service->validateAndClean($value, $this->language, $this->logViolations);

        if (! $result['valid']) {
            $fail('validation.no_profanity')->translate();
        }
    }
}
