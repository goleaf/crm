<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Models\People;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ContactDuplicateDetectionService
{
    /**
     * Find potential duplicates for a contact within the same team.
     *
     * @return Collection<int, array{contact: People, score: float}>
     */
    public function findDuplicates(People $contact, float $threshold = 0.75): Collection
    {
        if ($contact->team_id === null) {
            return collect();
        }

        $candidates = People::query()
            ->where('team_id', $contact->team_id)
            ->whereKeyNot($contact->getKey())
            ->get();

        return $candidates
            ->map(fn (People $candidate): array => [
                'contact' => $candidate,
                'score' => $this->calculateSimilarity($contact, $candidate),
            ])
            ->filter(fn (array $result): bool => $result['score'] >= $threshold)
            ->sortByDesc('score')
            ->values();
    }

    public function calculateSimilarity(People $a, People $b): float
    {
        $nameScore = $this->levenshteinSimilarity(
            $this->normalize($a->name),
            $this->normalize($b->name),
        );

        $emailScore = $this->matchEmail($a->primary_email, $b->primary_email);

        $phoneScore = $this->matchPhone(
            $a->phone_mobile ?? $a->phone_office ?? $a->phone_home,
            $b->phone_mobile ?? $b->phone_office ?? $b->phone_home,
        );

        return round(($nameScore * 0.4) + ($emailScore * 0.4) + ($phoneScore * 0.2), 2);
    }

    private function normalize(?string $value): string
    {
        return trim(Str::of((string) $value)->lower()->squish()->toString());
    }

    private function levenshteinSimilarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        $distance = levenshtein($a, $b);
        $maxLen = max(strlen($a), strlen($b));

        return $maxLen > 0 ? 1 - ($distance / $maxLen) : 0.0;
    }

    private function matchEmail(?string $a, ?string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        return $a === $b ? 1.0 : 0.0;
    }

    private function matchPhone(?string $a, ?string $b): float
    {
        $digitsA = preg_replace('/\D+/', '', (string) $a);
        $digitsB = preg_replace('/\D+/', '', (string) $b);

        if ($digitsA === '' || $digitsB === '') {
            return 0.0;
        }

        return $digitsA === $digitsB ? 1.0 : 0.0;
    }
}
