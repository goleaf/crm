<?php

declare(strict_types=1);

namespace App\Services\Content;

use Blaspsoft\Blasp\BlaspService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for filtering profanity from user-generated content.
 *
 * Wraps the Blasp package with application-specific logic including
 * caching, logging, and multi-language support.
 */
final readonly class ProfanityFilterService
{
    public function __construct(
        private BlaspService $blasp
    ) {}

    /**
     * Check if text contains profanity.
     */
    public function hasProfanity(string $text, ?string $language = null): bool
    {
        $service = $language ? $this->blasp->language($language) : $this->blasp;

        return $service->check($text)->hasProfanity();
    }

    /**
     * Clean text by masking profanities.
     */
    public function clean(
        string $text,
        ?string $language = null,
        ?string $maskCharacter = null
    ): string {
        $service = $language ? $this->blasp->language($language) : $this->blasp;

        if ($maskCharacter) {
            $service = $service->maskWith($maskCharacter);
        }

        return $service->check($text)->getCleanString();
    }

    /**
     * Get detailed profanity analysis.
     *
     * @return array{
     *     has_profanity: bool,
     *     count: int,
     *     unique_profanities: array<string>,
     *     clean_text: string,
     *     original_text: string
     * }
     */
    public function analyze(
        string $text,
        ?string $language = null,
        ?string $maskCharacter = null
    ): array {
        $service = $language ? $this->blasp->language($language) : $this->blasp;

        if ($maskCharacter) {
            $service = $service->maskWith($maskCharacter);
        }

        $result = $service->check($text);

        return [
            'has_profanity' => $result->hasProfanity(),
            'count' => $result->getProfanitiesCount(),
            'unique_profanities' => $result->getUniqueProfanitiesFound(),
            'clean_text' => $result->getCleanString(),
            'original_text' => $result->getSourceString(),
        ];
    }

    /**
     * Check text against all available languages.
     */
    public function checkAllLanguages(string $text, ?string $maskCharacter = null): array
    {
        $service = $this->blasp->allLanguages();

        if ($maskCharacter) {
            $service = $service->maskWith($maskCharacter);
        }

        $result = $service->check($text);

        return [
            'has_profanity' => $result->hasProfanity(),
            'count' => $result->getProfanitiesCount(),
            'unique_profanities' => $result->getUniqueProfanitiesFound(),
            'clean_text' => $result->getCleanString(),
            'original_text' => $result->getSourceString(),
        ];
    }

    /**
     * Validate and clean text in one operation.
     *
     * @return array{valid: bool, clean_text: string, profanities_found: array<string>}
     */
    public function validateAndClean(
        string $text,
        ?string $language = null,
        bool $logViolations = true
    ): array {
        $service = $language ? $this->blasp->language($language) : $this->blasp;
        $result = $service->check($text);

        $hasProfanity = $result->hasProfanity();

        if ($hasProfanity && $logViolations) {
            Log::warning('Profanity detected in user content', [
                'profanities' => $result->getUniqueProfanitiesFound(),
                'count' => $result->getProfanitiesCount(),
                'language' => $language ?? config('blasp.default_language'),
            ]);
        }

        return [
            'valid' => ! $hasProfanity,
            'clean_text' => $result->getCleanString(),
            'profanities_found' => $result->getUniqueProfanitiesFound(),
        ];
    }

    /**
     * Batch check multiple texts for profanity.
     *
     * @param  array<string>  $texts
     * @return array<int, bool>
     */
    public function batchCheck(array $texts, ?string $language = null): array
    {
        $service = $language ? $this->blasp->language($language) : $this->blasp;
        $results = [];

        foreach ($texts as $index => $text) {
            $results[$index] = $service->check($text)->hasProfanity();
        }

        return $results;
    }

    /**
     * Get cached profanity check result.
     */
    public function cachedCheck(
        string $text,
        ?string $language = null,
        int $ttl = 3600
    ): bool {
        $cacheKey = $this->getCacheKey($text, $language);

        return Cache::remember($cacheKey, $ttl, fn (): bool => $this->hasProfanity($text, $language));
    }

    /**
     * Generate cache key for profanity check.
     */
    private function getCacheKey(string $text, ?string $language): string
    {
        $lang = $language ?? config('blasp.default_language', 'english');

        return sprintf(
            'profanity_check:%s:%s',
            $lang,
            md5($text)
        );
    }

    /**
     * Clear profanity check cache.
     */
    public function clearCache(?string $text = null, ?string $language = null): void
    {
        if ($text) {
            Cache::forget($this->getCacheKey($text, $language));
        } else {
            // Clear all profanity check caches
            Cache::tags(['profanity_checks'])->flush();
        }
    }
}
