<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class AccountDuplicateDetectionService
{
    /**
     * Find potential duplicates for an account within the same team.
     *
     * @param  float  $threshold  Score threshold expressed as a percentage (0-100)
     * @return Collection<int, array{account: Account, score: float}>
     */
    public function find(Account $account, float $threshold = 60.0, int $limit = 5): Collection
    {
        $candidates = Account::query()
            ->where('team_id', $account->team_id)
            ->whereKeyNot($account->getKey())
            ->get();

        return $candidates
            ->map(fn (Account $candidate): array => [
                'account' => $candidate,
                'score' => $this->calculateScore($account, $candidate),
            ])
            ->filter(fn (array $candidate): bool => $candidate['score'] >= $threshold)
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    public function calculateScore(Account $primary, Account $candidate): float
    {
        if ($primary->is($candidate)) {
            return 100.0;
        }

        $score = 0.0;

        // Name similarity is the most important factor
        $score += 0.5 * $this->nameSimilarity($primary->name, $candidate->name);

        // Website/domain matching is highly indicative
        $score += 0.3 * $this->websiteSimilarity($primary->website, $candidate->website);

        // Phone number matching
        $score += 0.2 * $this->phoneSimilarity($primary, $candidate);

        return round(min(1.0, $score) * 100, 2);
    }

    private function nameSimilarity(?string $primary, ?string $candidate): float
    {
        if ($primary === null || $candidate === null) {
            return 0.0;
        }

        $normalizedPrimary = $this->normalizeName($primary);
        $normalizedCandidate = $this->normalizeName($candidate);

        $maxLength = max(strlen($normalizedPrimary), strlen($normalizedCandidate));

        if ($maxLength === 0) {
            return 0.0;
        }

        $distance = levenshtein($normalizedPrimary, $normalizedCandidate);

        return max(0.0, 1 - ($distance / $maxLength));
    }

    private function websiteSimilarity(?string $primary, ?string $candidate): float
    {
        $primaryDomain = $this->normalizeDomain($primary);
        $candidateDomain = $this->normalizeDomain($candidate);

        if ($primaryDomain === null || $candidateDomain === null) {
            return 0.0;
        }

        if ($primaryDomain === $candidateDomain) {
            return 1.0;
        }

        if (str_contains($primaryDomain, $candidateDomain) || str_contains($candidateDomain, $primaryDomain)) {
            return 0.8;
        }

        if ($this->registrableDomain($primaryDomain) === $this->registrableDomain($candidateDomain)) {
            return 0.6;
        }

        return 0.0;
    }

    private function phoneSimilarity(Account $primary, Account $candidate): float
    {
        $primaryPhones = $this->extractPhones($primary);
        $candidatePhones = $this->extractPhones($candidate);

        if ($primaryPhones === [] || $candidatePhones === []) {
            return 0.0;
        }

        foreach ($primaryPhones as $phone) {
            if (in_array($phone, $candidatePhones, true)) {
                return 1.0;
            }
        }

        return 0.0;
    }

    /**
     * Extract and normalize phone numbers from account addresses.
     *
     * @return array<int, string>
     */
    private function extractPhones(Account $account): array
    {
        $phones = [];

        // Extract from billing address
        if (is_array($account->billing_address) && isset($account->billing_address['phone'])) {
            $phones[] = $account->billing_address['phone'];
        }

        // Extract from shipping address
        if (is_array($account->shipping_address) && isset($account->shipping_address['phone'])) {
            $phones[] = $account->shipping_address['phone'];
        }

        return $this->normalizePhones($phones);
    }

    /**
     * @param  array<int, ?string>  $phones
     * @return array<int, string>
     */
    private function normalizePhones(array $phones): array
    {
        return array_values(array_filter(array_map(
            function (?string $phone): ?string {
                if (! is_string($phone) || trim($phone) === '') {
                    return null;
                }

                $digits = preg_replace('/\D+/', '', $phone);

                return $digits !== '' ? $digits : null;
            },
            $phones
        )));
    }

    private function normalizeName(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function normalizeDomain(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST) ?? $url;

        $normalized = Str::of($host)
            ->lower()
            ->replaceMatches('/^www\./', '')
            ->trim()
            ->toString();

        return $normalized === '' ? null : $normalized;
    }

    private function registrableDomain(string $domain): string
    {
        $segments = array_values(array_filter(explode('.', $domain)));

        if (count($segments) <= 2) {
            return $domain;
        }

        return implode('.', array_slice($segments, -2));
    }
}
