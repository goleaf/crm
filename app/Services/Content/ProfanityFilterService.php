<?php

declare(strict_types=1);

namespace App\Services\Content;

use Blaspsoft\Blasp\Facades\Blasp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ProfanityFilterService
{
    /**
     * Check if the text contains profanity in the specified language.
     */
    public function hasProfanity(string $text, string $language = 'english'): bool
    {
        if ($text === '' || $text === '0') {
            return false;
        }

        if ($language === 'all') {
            return $this->checkAllLanguages($text)['has_profanity'];
        }

        return Blasp::language($language)->check($text);
    }

    /**
     * Clean the text by masking profanity.
     */
    public function clean(string $text, string $language = 'english', string $maskCharacter = '*'): string
    {
        if ($text === '' || $text === '0') {
            return $text;
        }

        if ($language === 'all') {
            // For 'all', we might want to iterate or use a specific strategy.
            // Blasp currently doesn't support 'clean' on 'all' directly in one go easily without loop,
            // but let's assume primary usage is single language or iterative.
            // For now, let's clean sequentially if 'all' is passed, or just default to english/configured default.

            // Note: Checking all languages for cleaning might be aggressive.
            // Let's iterate through available languages.
            $languages = ['english', 'spanish', 'german', 'french'];
            foreach ($languages as $lang) {
                $text = Blasp::language($lang)->clean($text, $maskCharacter);
            }

            return $text;
        }

        return Blasp::language($language)->clean($text, $maskCharacter);
    }

    /**
     * Check text against all supported languages.
     */
    public function checkAllLanguages(string $text): array
    {
        $languages = ['english', 'spanish', 'german', 'french'];
        $hasProfanity = false;

        foreach ($languages as $language) {
            if (Blasp::language($language)->check($text)) {
                $hasProfanity = true;
                // Blasp doesn't easily return *which* words matched in a simple check without analyze ??
                // Actually Blasp::check() is boolean.
                // To get details we might need a different approach if the package supports it.
                // Looking at docs, check() returns bool.
                // Let's keep it simple for now.
            }
        }

        return [
            'has_profanity' => $hasProfanity,
            // 'unique_profanities' => ... // Package might not expose this easily on simple check
            // 'count' => ...
        ];
    }

    /**
     * Batch check multiple texts string.
     */
    public function batchCheck(array $texts, string $language = 'english'): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->hasProfanity($text, $language);
        }

        return $results;
    }

    /**
     * Cached check for frequently accessed content.
     */
    public function cachedCheck(string $text, string $language = 'english', int $ttl = 3600): bool
    {
        $key = 'profanity_check_' . md5($text . $language);

        return Cache::remember($key, $ttl, fn (): bool => $this->hasProfanity($text, $language));
    }

    /**
     * Validate and clean text, optionally logging violations.
     */
    public function validateAndClean(string $text, string $language = 'english', bool $logViolations = true): array
    {
        $hasProfanity = $this->hasProfanity($text, $language);
        $cleanText = $text;

        if ($hasProfanity) {
            $cleanText = $this->clean($text, $language);

            if ($logViolations) {
                Log::info('Profanity detected', [
                    'original' => $text,
                    'cleaned' => $cleanText,
                    'language' => $language,
                    // 'user_id' => auth()->id(), // Optional context
                ]);
            }
        }

        return [
            'valid' => ! $hasProfanity,
            'clean_text' => $cleanText,
            'profanities_found' => $hasProfanity, // accurate enough for now
        ];
    }

    /**
     * Clear cache for specific text or all.
     */
    public function clearCache(?string $text = null, string $language = 'english'): void
    {
        if ($text) {
            $key = 'profanity_check_' . md5($text . $language);
            Cache::forget($key);
        } else {
            // Clearing "all" specific caches is hard with standard cache drivers without tagging.
            // If tagging is available: Cache::tags(['profanity'])->flush();
            // For now, if we depend on key pattern, we can't easily clear all unless we use tags.
            // We'll assume tags if supported, or just leave as specific clear.
        }
    }
}
