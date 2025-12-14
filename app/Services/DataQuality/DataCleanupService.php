<?php

declare(strict_types=1);

namespace App\Services\DataQuality;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DataCleanupService
{
    /**
     * Clean up data based on specified rules.
     */
    public function cleanup(array $rules, ?int $teamId = null): array
    {
        $results = [
            'total_cleaned' => 0,
            'operations' => [],
            'errors' => [],
        ];

        foreach ($rules as $rule) {
            try {
                $result = $this->executeCleanupRule($rule, $teamId);
                $results['operations'][] = $result;
                $results['total_cleaned'] += $result['cleaned_count'];
            } catch (\Throwable $e) {
                $error = [
                    'rule' => $rule['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                $results['errors'][] = $error;

                Log::error('Data cleanup rule failed', [
                    'rule' => $rule,
                    'error' => $e->getMessage(),
                    'team_id' => $teamId,
                ]);
            }
        }

        return $results;
    }

    /**
     * Execute a specific cleanup rule.
     */
    private function executeCleanupRule(array $rule, ?int $teamId): array
    {
        return match ($rule['type']) {
            'remove_empty_records' => $this->removeEmptyRecords($rule, $teamId),
            'normalize_phone_numbers' => $this->normalizePhoneNumbers($rule, $teamId),
            'normalize_email_addresses' => $this->normalizeEmailAddresses($rule, $teamId),
            'remove_duplicate_spaces' => $this->removeDuplicateSpaces($rule, $teamId),
            'standardize_country_codes' => $this->standardizeCountryCodes($rule, $teamId),
            'clean_website_urls' => $this->cleanWebsiteUrls($rule, $teamId),
            'remove_invalid_data' => $this->removeInvalidData($rule, $teamId),
            'merge_duplicate_tags' => $this->mergeDuplicateTags($rule),
            default => throw new \InvalidArgumentException("Unknown cleanup rule type: {$rule['type']}"),
        };
    }

    /**
     * Remove records that are essentially empty.
     */
    private function removeEmptyRecords(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $requiredFields = $rule['required_fields'] ?? [];
        $cleanedCount = 0;

        $query = DB::table($table)->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        // Build condition for empty records
        $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($requiredFields): void {
            foreach ($requiredFields as $field) {
                $q->where(function (\Illuminate\Contracts\Database\Query\Builder $subQ) use ($field): void {
                    $subQ->whereNull($field)->orWhere($field, '');
                });
            }
        });

        $cleanedCount = $rule['dry_run'] ?? false ? $query->count() : $query->delete();

        return [
            'rule_name' => $rule['name'] ?? 'Remove Empty Records',
            'type' => 'remove_empty_records',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Removed {$cleanedCount} empty records from {$table}",
        ];
    }

    /**
     * Normalize phone numbers to a standard format.
     */
    private function normalizePhoneNumbers(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $phoneField = $rule['phone_field'] ?? 'phone';
        $cleanedCount = 0;

        $query = DB::table($table)
            ->whereNotNull($phoneField)
            ->where($phoneField, '!=', '')
            ->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            $originalPhone = $record->{$phoneField};
            $normalizedPhone = $this->normalizePhoneNumber($originalPhone);

            if ($originalPhone !== $normalizedPhone) {
                if (! ($rule['dry_run'] ?? false)) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$phoneField => $normalizedPhone]);
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Normalize Phone Numbers',
            'type' => 'normalize_phone_numbers',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Normalized {$cleanedCount} phone numbers in {$table}",
        ];
    }

    /**
     * Normalize email addresses.
     */
    private function normalizeEmailAddresses(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $emailField = $rule['email_field'] ?? 'email';
        $cleanedCount = 0;

        $query = DB::table($table)
            ->whereNotNull($emailField)
            ->where($emailField, '!=', '')
            ->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            $originalEmail = $record->{$emailField};
            $normalizedEmail = strtolower(trim((string) $originalEmail));

            if ($originalEmail !== $normalizedEmail) {
                if (! ($rule['dry_run'] ?? false)) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$emailField => $normalizedEmail]);
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Normalize Email Addresses',
            'type' => 'normalize_email_addresses',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Normalized {$cleanedCount} email addresses in {$table}",
        ];
    }

    /**
     * Remove duplicate spaces from text fields.
     */
    private function removeDuplicateSpaces(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $textFields = $rule['text_fields'] ?? [];
        $cleanedCount = 0;

        $query = DB::table($table)->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            $updates = [];
            $hasChanges = false;

            foreach ($textFields as $field) {
                if (isset($record->{$field}) && is_string($record->{$field})) {
                    $original = $record->{$field};
                    $cleaned = preg_replace('/\s+/', ' ', trim($original));

                    if ($original !== $cleaned) {
                        $updates[$field] = $cleaned;
                        $hasChanges = true;
                    }
                }
            }

            if ($hasChanges) {
                if (! ($rule['dry_run'] ?? false)) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update($updates);
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Remove Duplicate Spaces',
            'type' => 'remove_duplicate_spaces',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Cleaned duplicate spaces in {$cleanedCount} records from {$table}",
        ];
    }

    /**
     * Standardize country codes.
     */
    private function standardizeCountryCodes(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $countryField = $rule['country_field'] ?? 'country';
        $cleanedCount = 0;

        // Common country name to ISO code mappings
        $countryMappings = [
            'United States' => 'US',
            'United States of America' => 'US',
            'USA' => 'US',
            'United Kingdom' => 'GB',
            'UK' => 'GB',
            'Great Britain' => 'GB',
            'Canada' => 'CA',
            'Australia' => 'AU',
            'Germany' => 'DE',
            'France' => 'FR',
            'Spain' => 'ES',
            'Italy' => 'IT',
            'Japan' => 'JP',
            'China' => 'CN',
        ];

        $query = DB::table($table)
            ->whereNotNull($countryField)
            ->where($countryField, '!=', '')
            ->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            $originalCountry = $record->{$countryField};
            $standardizedCountry = $countryMappings[$originalCountry] ?? $originalCountry;

            if ($originalCountry !== $standardizedCountry) {
                if (! ($rule['dry_run'] ?? false)) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$countryField => $standardizedCountry]);
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Standardize Country Codes',
            'type' => 'standardize_country_codes',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Standardized {$cleanedCount} country codes in {$table}",
        ];
    }

    /**
     * Clean website URLs.
     */
    private function cleanWebsiteUrls(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $urlField = $rule['url_field'] ?? 'website';
        $cleanedCount = 0;

        $query = DB::table($table)
            ->whereNotNull($urlField)
            ->where($urlField, '!=', '')
            ->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            $originalUrl = $record->{$urlField};
            $cleanedUrl = $this->cleanUrl($originalUrl);

            if ($originalUrl !== $cleanedUrl) {
                if (! ($rule['dry_run'] ?? false)) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$urlField => $cleanedUrl]);
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Clean Website URLs',
            'type' => 'clean_website_urls',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Cleaned {$cleanedCount} website URLs in {$table}",
        ];
    }

    /**
     * Remove invalid data based on validation rules.
     */
    private function removeInvalidData(array $rule, ?int $teamId): array
    {
        $table = $rule['table'];
        $validationRules = $rule['validation_rules'] ?? [];
        $cleanedCount = 0;

        $query = DB::table($table)->whereNull('deleted_at');

        if ($teamId && in_array('team_id', DB::getSchemaBuilder()->getColumnListing($table))) {
            $query->where('team_id', $teamId);
        }

        // Apply validation rules to find invalid records
        foreach ($validationRules as $field => $validation) {
            if ($validation['type'] === 'email') {
                $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($field): void {
                    $q->whereNull($field)
                        ->orWhere($field, '')
                        ->orWhere($field, 'NOT REGEXP', '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');
                });
            }
        }

        if ($rule['dry_run'] ?? false) {
            $cleanedCount = $query->count();
        } elseif ($rule['action'] === 'delete') {
            $cleanedCount = $query->delete();
        } elseif ($rule['action'] === 'nullify') {
            $records = $query->get();
            foreach ($records as $record) {
                $updates = [];
                foreach ($validationRules as $field => $validation) {
                    $updates[$field] = null;
                }
                DB::table($table)->where('id', $record->id)->update($updates);
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Remove Invalid Data',
            'type' => 'remove_invalid_data',
            'table' => $table,
            'cleaned_count' => $cleanedCount,
            'description' => "Cleaned {$cleanedCount} invalid records from {$table}",
        ];
    }

    /**
     * Merge duplicate tags.
     */
    private function mergeDuplicateTags(array $rule): array
    {
        $cleanedCount = 0;

        // Find duplicate tags (case-insensitive)
        $duplicateTags = DB::table('tags')
            ->select(DB::raw('LOWER(name) as lower_name'), DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
            ->groupBy(DB::raw('LOWER(name)'))
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateTags as $duplicateGroup) {
            $tagsToMerge = DB::table('tags')
                ->where(DB::raw('LOWER(name)'), $duplicateGroup->lower_name)
                ->where('id', '!=', $duplicateGroup->keep_id)
                ->get();

            foreach ($tagsToMerge as $tagToMerge) {
                if (! ($rule['dry_run'] ?? false)) {
                    // Update all relationships to point to the kept tag
                    DB::table('taggables')
                        ->where('tag_id', $tagToMerge->id)
                        ->update(['tag_id' => $duplicateGroup->keep_id]);

                    // Delete the duplicate tag
                    DB::table('tags')->where('id', $tagToMerge->id)->delete();
                }
                $cleanedCount++;
            }
        }

        return [
            'rule_name' => $rule['name'] ?? 'Merge Duplicate Tags',
            'type' => 'merge_duplicate_tags',
            'table' => 'tags',
            'cleaned_count' => $cleanedCount,
            'description' => "Merged {$cleanedCount} duplicate tags",
        ];
    }

    /**
     * Normalize a phone number to a standard format.
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // If it starts with +1, keep it
        if (str_starts_with((string) $cleaned, '+1')) {
            return $cleaned;
        }

        // If it's 10 digits and doesn't start with +, assume US number
        if (strlen((string) $cleaned) === 10 && ! str_starts_with((string) $cleaned, '+')) {
            return '+1' . $cleaned;
        }

        // If it's 11 digits and starts with 1, assume US number
        if (strlen((string) $cleaned) === 11 && str_starts_with((string) $cleaned, '1')) {
            return '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Clean a URL to a standard format.
     */
    private function cleanUrl(string $url): string
    {
        $url = trim($url);

        // Add protocol if missing
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        // Remove trailing slash
        $url = rtrim($url, '/');

        // Convert to lowercase domain
        $parsed = parse_url($url);
        if (isset($parsed['host'])) {
            $parsed['host'] = strtolower($parsed['host']);
            $url = $this->buildUrl($parsed);
        }

        return $url;
    }

    /**
     * Build URL from parsed components.
     */
    private function buildUrl(array $parsed): string
    {
        $url = '';

        if (isset($parsed['scheme'])) {
            $url .= $parsed['scheme'] . '://';
        }

        if (isset($parsed['host'])) {
            $url .= $parsed['host'];
        }

        if (isset($parsed['port'])) {
            $url .= ':' . $parsed['port'];
        }

        if (isset($parsed['path'])) {
            $url .= $parsed['path'];
        }

        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        if (isset($parsed['fragment'])) {
            $url .= '#' . $parsed['fragment'];
        }

        return $url;
    }
}
