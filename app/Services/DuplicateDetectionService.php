<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Industry;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class DuplicateDetectionService
{
    /**
     * @param  float  $threshold  Score threshold expressed as a percentage (0-100)
     */
    public function findDuplicates(Company $company, float $threshold = 60.0, int $limit = 5): Collection
    {
        $thresholdNormalized = max(0.0, min(1.0, $threshold / 100));

        return Company::query()
            ->where('id', '!=', $company->getKey())
            ->get()
            ->map(fn (Company $candidate): array => [
                'company' => $candidate,
                'score' => $this->calculateSimilarity($company, $candidate),
            ])
            ->filter(fn (array $candidate): bool => ($candidate['score'] / 100) >= $thresholdNormalized)
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    public function calculateSimilarity(Company $primary, Company $duplicate): float
    {
        if ($primary->getKey() === $duplicate->getKey()) {
            return 100.0;
        }

        $score = 0.0;
        $score += 0.6 * $this->nameSimilarity($primary->name, $duplicate->name);
        $score += 0.3 * $this->websiteSimilarity($primary->website, $duplicate->website);
        $score += 0.1 * ($this->sameIndustry($primary->industry, $duplicate->industry) ? 1.0 : 0.0);

        return round(min(1.0, max(0.0, $score)) * 100, 2);
    }

    /**
     * Provide a recommended merge map for each field.
     *
     * @return Collection<int, array{attribute: string, label: string, primary: mixed, duplicate: mixed, selected: mixed}>
     */
    public function suggestMerge(Company $primary, Company $duplicate): Collection
    {
        $attributes = [
            'name' => 'Company Name',
            'website' => 'Website',
            'industry' => 'Industry',
            'address' => 'Address',
            'phone' => 'Phone',
            'revenue' => 'Annual Revenue',
            'employee_count' => 'Employee Count',
            'description' => 'Description',
        ];

        return collect($attributes)->map(function (string $label, string $attribute) use ($primary, $duplicate): array {
            $primaryValue = $primary->{$attribute};
            $duplicateValue = $duplicate->{$attribute};

            return [
                'attribute' => $attribute,
                'label' => $label,
                'primary' => $this->formatMergeValue($primaryValue),
                'duplicate' => $this->formatMergeValue($duplicateValue),
                'selected' => $this->formatMergeValue($this->preferValue($primaryValue, $duplicateValue)),
            ];
        });
    }

    private function nameSimilarity(?string $primary, ?string $duplicate): float
    {
        if (! $primary || ! $duplicate) {
            return 0.0;
        }

        $normalizedPrimary = $this->normalizeName($primary);
        $normalizedDuplicate = $this->normalizeName($duplicate);

        $maxLength = max(strlen($normalizedPrimary), strlen($normalizedDuplicate));

        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($normalizedPrimary, $normalizedDuplicate);

        return max(0.0, 1 - ($distance / $maxLength));
    }

    private function websiteSimilarity(?string $primary, ?string $duplicate): float
    {
        $primaryDomain = $this->normalizeDomain($primary);
        $duplicateDomain = $this->normalizeDomain($duplicate);

        if ($primaryDomain === null || $duplicateDomain === null) {
            return 0.0;
        }

        if ($primaryDomain === $duplicateDomain) {
            return 1.0;
        }

        if (str_contains($primaryDomain, $duplicateDomain) || str_contains($duplicateDomain, $primaryDomain)) {
            return 0.8;
        }

        if ($this->registrableDomain($primaryDomain) === $this->registrableDomain($duplicateDomain)) {
            return 0.6;
        }

        return 0.2;
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

    private function sameIndustry(Industry|string|null $primary, Industry|string|null $duplicate): bool
    {
        $primaryValue = $this->normalizeIndustry($primary);
        $duplicateValue = $this->normalizeIndustry($duplicate);

        if ($primaryValue === null || $duplicateValue === null) {
            return false;
        }

        return $primaryValue === $duplicateValue;
    }

    private function normalizeIndustry(Industry|string|null $industry): ?string
    {
        if ($industry instanceof Industry) {
            return $industry->value;
        }

        if (! is_string($industry) || trim($industry) === '') {
            return null;
        }

        $normalized = Str::of($industry)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $normalized === '' ? null : $normalized;
    }

    private function preferValue(mixed $primary, mixed $duplicate): mixed
    {
        if ($this->isMeaningful($primary)) {
            return $primary;
        }

        if ($this->isMeaningful($duplicate)) {
            return $duplicate;
        }

        return $primary;
    }

    private function isMeaningful(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return ! (is_string($value) && trim($value) === '');
    }

    private function formatMergeValue(mixed $value): mixed
    {
        if ($value instanceof Industry) {
            return $value->label();
        }

        return $value;
    }
}
