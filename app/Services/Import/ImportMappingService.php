<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportJob;

final class ImportMappingService
{
    public function suggestMapping(ImportJob $importJob): array
    {
        $headers = $importJob->preview_data['headers'] ?? [];
        $modelType = $importJob->model_type;

        $suggestions = [];
        $fieldMappings = $this->getFieldMappings($modelType);

        foreach ($headers as $header) {
            $normalizedHeader = $this->normalizeHeader($header);

            // Find best match
            $bestMatch = null;
            $bestScore = 0;

            foreach ($fieldMappings as $modelField => $patterns) {
                $score = $this->calculateMatchScore($normalizedHeader, $patterns);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $modelField;
                }
            }

            if ($bestMatch && $bestScore > 0.5) {
                $suggestions[$bestMatch] = $header;
            }
        }

        return $suggestions;
    }

    public function getAvailableFields(string $modelType): array
    {
        $fieldMappings = $this->getFieldMappings($modelType);

        return array_map(fn (int|string $field): array => [
            'value' => $field,
            'label' => $this->getFieldLabel($field),
        ], array_keys($fieldMappings));
    }

    private function getFieldMappings(string $modelType): array
    {
        $mappings = [
            'Company' => [
                'name' => ['name', 'company_name', 'company', 'organization', 'org'],
                'email' => ['email', 'email_address', 'e_mail', 'mail'],
                'phone' => ['phone', 'telephone', 'tel', 'phone_number', 'contact_number'],
                'website' => ['website', 'url', 'web', 'site', 'homepage'],
                'address' => ['address', 'street', 'location', 'addr'],
                'city' => ['city', 'town', 'locality'],
                'state' => ['state', 'province', 'region'],
                'postal_code' => ['postal_code', 'zip', 'zip_code', 'postcode'],
                'country' => ['country', 'nation'],
                'industry' => ['industry', 'sector', 'business_type'],
                'description' => ['description', 'notes', 'comments', 'about'],
            ],
            'People' => [
                'first_name' => ['first_name', 'firstname', 'fname', 'given_name'],
                'last_name' => ['last_name', 'lastname', 'lname', 'surname', 'family_name'],
                'email' => ['email', 'email_address', 'e_mail', 'mail'],
                'phone' => ['phone', 'telephone', 'tel', 'phone_number', 'mobile'],
                'title' => ['title', 'job_title', 'position', 'role'],
                'company_name' => ['company', 'company_name', 'organization', 'employer'],
                'address' => ['address', 'street', 'location'],
                'city' => ['city', 'town'],
                'state' => ['state', 'province'],
                'postal_code' => ['postal_code', 'zip', 'zip_code'],
                'country' => ['country'],
            ],
            'Lead' => [
                'name' => ['name', 'lead_name', 'contact_name', 'full_name'],
                'email' => ['email', 'email_address', 'e_mail'],
                'phone' => ['phone', 'telephone', 'tel', 'contact_number'],
                'company' => ['company', 'company_name', 'organization'],
                'title' => ['title', 'job_title', 'position'],
                'source' => ['source', 'lead_source', 'origin'],
                'status' => ['status', 'lead_status', 'stage'],
                'notes' => ['notes', 'comments', 'description'],
            ],
            'Opportunity' => [
                'title' => ['title', 'name', 'opportunity_name', 'deal_name'],
                'value' => ['value', 'amount', 'deal_value', 'opportunity_value'],
                'close_date' => ['close_date', 'expected_close', 'closing_date'],
                'stage' => ['stage', 'status', 'phase'],
                'probability' => ['probability', 'win_probability', 'chance'],
                'description' => ['description', 'notes', 'comments'],
            ],
        ];

        return $mappings[$modelType] ?? [];
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]/', '_', $header)));
    }

    private function calculateMatchScore(string $header, array $patterns): float
    {
        $maxScore = 0;

        foreach ($patterns as $pattern) {
            $normalizedPattern = $this->normalizeHeader($pattern);

            // Exact match
            if ($header === $normalizedPattern) {
                return 1.0;
            }

            // Partial match
            if (str_contains($header, $normalizedPattern) || str_contains($normalizedPattern, $header)) {
                $score = 0.8;
            } else {
                // Levenshtein distance
                $distance = levenshtein($header, $normalizedPattern);
                $maxLength = max(strlen($header), strlen($normalizedPattern));
                $score = 1 - ($distance / $maxLength);
            }

            $maxScore = max($maxScore, $score);
        }

        return $maxScore;
    }

    private function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Name',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'website' => 'Website',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postal_code' => 'Postal Code',
            'country' => 'Country',
            'title' => 'Title',
            'company_name' => 'Company Name',
            'industry' => 'Industry',
            'description' => 'Description',
            'value' => 'Value',
            'close_date' => 'Close Date',
            'stage' => 'Stage',
            'probability' => 'Probability',
            'source' => 'Source',
            'status' => 'Status',
            'notes' => 'Notes',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }
}
