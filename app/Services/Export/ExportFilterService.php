<?php

declare(strict_types=1);

namespace App\Services\Export;

use Illuminate\Database\Eloquent\Builder;

final class ExportFilterService
{
    /**
     * Apply filters to a query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            $this->applyFilter($query, $field, $value);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query
     */
    private function applyFilter(Builder $query, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Handle date range filters
        if (is_array($value) && (isset($value['from']) || isset($value['to']))) {
            $this->applyDateRangeFilter($query, $field, $value);

            return;
        }

        // Handle array values (multiple selection)
        if (is_array($value)) {
            $query->whereIn($field, $value);

            return;
        }

        // Handle different field types
        match ($field) {
            'created_at', 'updated_at', 'due_date', 'expected_close_date', 'actual_close_date', 'completed_at' => $this->applyDateFilter($query, $field, $value),
            'name', 'title', 'description', 'email' => $query->where($field, 'LIKE', "%{$value}%"),
            'status', 'stage', 'priority', 'category', 'visibility', 'industry', 'size' => $query->where($field, $value),
            'value', 'annual_revenue', 'probability' => $this->applyNumericFilter($query, $field, $value),
            // Generic equality filter
            default => $query->where($field, $value),
        };
    }

    /**
     * Apply date range filter
     */
    private function applyDateRangeFilter(Builder $query, string $field, array $range): void
    {
        if (isset($range['from']) && $range['from']) {
            $from = \Illuminate\Support\Facades\Date::parse($range['from'])->startOfDay();
            $query->where($field, '>=', $from);
        }

        if (isset($range['to']) && $range['to']) {
            $to = \Illuminate\Support\Facades\Date::parse($range['to'])->endOfDay();
            $query->where($field, '<=', $to);
        }
    }

    /**
     * Apply date filter
     */
    private function applyDateFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_string($value)) {
            try {
                $date = \Illuminate\Support\Facades\Date::parse($value);
                $query->whereDate($field, $date->toDateString());
            } catch (\Exception) {
                // Invalid date format, skip filter
            }
        }
    }

    /**
     * Apply numeric filter
     */
    private function applyNumericFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            // Handle range filters like ['min' => 1000, 'max' => 5000]
            if (isset($value['min']) && is_numeric($value['min'])) {
                $query->where($field, '>=', $value['min']);
            }
            if (isset($value['max']) && is_numeric($value['max'])) {
                $query->where($field, '<=', $value['max']);
            }
        } elseif (is_numeric($value)) {
            $query->where($field, $value);
        }
    }

    /**
     * Get available filter options for a model type
     */
    public function getAvailableFilters(string $modelType): array
    {
        $filterMap = [
            'Company' => [
                'name' => ['type' => 'text', 'label' => 'Company Name'],
                'industry' => ['type' => 'select', 'label' => 'Industry'],
                'size' => ['type' => 'select', 'label' => 'Company Size'],
                'annual_revenue' => ['type' => 'number_range', 'label' => 'Annual Revenue'],
                'created_at' => ['type' => 'date_range', 'label' => 'Created Date'],
                'updated_at' => ['type' => 'date_range', 'label' => 'Updated Date'],
            ],
            'People' => [
                'first_name' => ['type' => 'text', 'label' => 'First Name'],
                'last_name' => ['type' => 'text', 'label' => 'Last Name'],
                'email' => ['type' => 'text', 'label' => 'Email'],
                'job_title' => ['type' => 'text', 'label' => 'Job Title'],
                'department' => ['type' => 'text', 'label' => 'Department'],
                'created_at' => ['type' => 'date_range', 'label' => 'Created Date'],
                'updated_at' => ['type' => 'date_range', 'label' => 'Updated Date'],
            ],
            'Opportunity' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'stage' => ['type' => 'select', 'label' => 'Stage'],
                'value' => ['type' => 'number_range', 'label' => 'Value'],
                'probability' => ['type' => 'number_range', 'label' => 'Probability'],
                'expected_close_date' => ['type' => 'date_range', 'label' => 'Expected Close Date'],
                'actual_close_date' => ['type' => 'date_range', 'label' => 'Actual Close Date'],
                'created_at' => ['type' => 'date_range', 'label' => 'Created Date'],
            ],
            'Task' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'status' => ['type' => 'select', 'label' => 'Status'],
                'priority' => ['type' => 'select', 'label' => 'Priority'],
                'due_date' => ['type' => 'date_range', 'label' => 'Due Date'],
                'completed_at' => ['type' => 'date_range', 'label' => 'Completed Date'],
                'created_at' => ['type' => 'date_range', 'label' => 'Created Date'],
            ],
            'Note' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'category' => ['type' => 'select', 'label' => 'Category'],
                'visibility' => ['type' => 'select', 'label' => 'Visibility'],
                'is_template' => ['type' => 'boolean', 'label' => 'Is Template'],
                'created_at' => ['type' => 'date_range', 'label' => 'Created Date'],
            ],
        ];

        return $filterMap[$modelType] ?? [];
    }
}
