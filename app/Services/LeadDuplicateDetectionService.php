<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LeadDuplicateDetectionService
{
    /**
     * Find potential duplicates for a lead within the same team.
     *
     * @return Collection<int, array{lead: Lead, score: float}>
     */
    public function find(Lead $lead, float $threshold = 60.0, int $limit = 5): Collection
    {
        $candidates = Lead::query()
            ->where('team_id', $lead->team_id)
            ->whereKeyNot($lead->getKey())
            ->get();

        return $candidates
            ->map(fn (Lead $candidate): array => [
                'lead' => $candidate,
                'score' => $this->calculateScore($lead, $candidate),
            ])
            ->filter(fn (array $candidate): bool => $candidate['score'] >= $threshold)
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    public function calculateScore(Lead $primary, Lead $candidate): float
    {
        if ($primary->is($candidate)) {
            return 100.0;
        }

        $score = 0.0;

        $score += 0.7 * $this->emailSimilarity($primary->email, $candidate->email);
        $score += 0.2 * $this->phoneSimilarity($primary, $candidate);
        $score += 0.1 * $this->nameSimilarity($primary->name, $candidate->name);

        return round(min(1.0, $score) * 100, 2);
    }

    private function emailSimilarity(?string $primary, ?string $candidate): float
    {
        $normalizedPrimary = $this->normalizeEmail($primary);
        $normalizedCandidate = $this->normalizeEmail($candidate);

        if ($normalizedPrimary === null || $normalizedCandidate === null) {
            return 0.0;
        }

        return $normalizedPrimary === $normalizedCandidate ? 1.0 : 0.0;
    }

    private function phoneSimilarity(Lead $primary, Lead $candidate): float
    {
        $primaryPhones = $this->normalizePhones([$primary->phone, $primary->mobile]);
        $candidatePhones = $this->normalizePhones([$candidate->phone, $candidate->mobile]);

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

    private function normalizeEmail(?string $email): ?string
    {
        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    /**
     * @param array<int, ?string> $phones
     *
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
            $phones,
        )));
    }

    private function normalizeName(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
