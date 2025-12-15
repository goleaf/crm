<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class ExportTemplateService
{
    /**
     * Get available export templates for a model type
     */
    public function getTemplatesForModel(string $modelType, Team $team): Collection
    {
        $cacheKey = "export_templates_{$modelType}_{$team->id}";

        return Cache::remember($cacheKey, 3600, function () use ($modelType) {
            // Get built-in templates
            $builtInTemplates = $this->getBuiltInTemplates($modelType);

            // Get custom templates (could be stored in database in the future)
            $customTemplates = $this->getCustomTemplates();

            return $builtInTemplates->merge($customTemplates);
        });
    }

    /**
     * Get available fields for export for a model type
     */
    public function getAvailableFields(string $modelType): array
    {
        $fieldMap = [
            'Company' => [
                'id' => ['label' => 'ID', 'type' => 'integer'],
                'name' => ['label' => 'Company Name', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string'],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'website' => ['label' => 'Website', 'type' => 'string'],
                'industry' => ['label' => 'Industry', 'type' => 'string'],
                'size' => ['label' => 'Company Size', 'type' => 'string'],
                'annual_revenue' => ['label' => 'Annual Revenue', 'type' => 'decimal'],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'account_owner.name' => ['label' => 'Account Owner', 'type' => 'string'],
                'team.name' => ['label' => 'Team', 'type' => 'string'],
                'people_count' => ['label' => 'Number of People', 'type' => 'integer'],
                'opportunities_count' => ['label' => 'Number of Opportunities', 'type' => 'integer'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            ],
            'People' => [
                'id' => ['label' => 'ID', 'type' => 'integer'],
                'first_name' => ['label' => 'First Name', 'type' => 'string'],
                'last_name' => ['label' => 'Last Name', 'type' => 'string'],
                'full_name' => ['label' => 'Full Name', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string'],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'mobile' => ['label' => 'Mobile', 'type' => 'string'],
                'job_title' => ['label' => 'Job Title', 'type' => 'string'],
                'department' => ['label' => 'Department', 'type' => 'string'],
                'company.name' => ['label' => 'Company', 'type' => 'string'],
                'account_owner.name' => ['label' => 'Account Owner', 'type' => 'string'],
                'team.name' => ['label' => 'Team', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            ],
            'Opportunity' => [
                'id' => ['label' => 'ID', 'type' => 'integer'],
                'title' => ['label' => 'Title', 'type' => 'string'],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'value' => ['label' => 'Value', 'type' => 'decimal'],
                'stage' => ['label' => 'Stage', 'type' => 'string'],
                'probability' => ['label' => 'Probability', 'type' => 'integer'],
                'expected_close_date' => ['label' => 'Expected Close Date', 'type' => 'date'],
                'actual_close_date' => ['label' => 'Actual Close Date', 'type' => 'date'],
                'company.name' => ['label' => 'Company', 'type' => 'string'],
                'contact.full_name' => ['label' => 'Primary Contact', 'type' => 'string'],
                'account_owner.name' => ['label' => 'Account Owner', 'type' => 'string'],
                'team.name' => ['label' => 'Team', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            ],
            'Task' => [
                'id' => ['label' => 'ID', 'type' => 'integer'],
                'title' => ['label' => 'Title', 'type' => 'string'],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'status' => ['label' => 'Status', 'type' => 'string'],
                'priority' => ['label' => 'Priority', 'type' => 'string'],
                'due_date' => ['label' => 'Due Date', 'type' => 'datetime'],
                'completed_at' => ['label' => 'Completed At', 'type' => 'datetime'],
                'assigned_to.name' => ['label' => 'Assigned To', 'type' => 'string'],
                'creator.name' => ['label' => 'Created By', 'type' => 'string'],
                'team.name' => ['label' => 'Team', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            ],
            'Note' => [
                'id' => ['label' => 'ID', 'type' => 'integer'],
                'title' => ['label' => 'Title', 'type' => 'string'],
                'content' => ['label' => 'Content', 'type' => 'text'],
                'category' => ['label' => 'Category', 'type' => 'string'],
                'visibility' => ['label' => 'Visibility', 'type' => 'string'],
                'is_template' => ['label' => 'Is Template', 'type' => 'boolean'],
                'creator.name' => ['label' => 'Created By', 'type' => 'string'],
                'team.name' => ['label' => 'Team', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime'],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime'],
            ],
        ];

        return $fieldMap[$modelType] ?? [];
    }

    /**
     * Create a custom template
     */
    public function createTemplate(string $modelType, Team $team, array $config): array
    {
        // In a full implementation, this would save to database
        // For now, return the template configuration
        return [
            'id' => uniqid(),
            'name' => $config['name'],
            'description' => $config['description'] ?? '',
            'model_type' => $modelType,
            'team_id' => $team->id,
            'fields' => $config['fields'],
            'format_options' => $config['format_options'] ?? $this->getDefaultFormatOptions(),
            'is_custom' => true,
            'created_at' => now(),
        ];
    }

    /**
     * Get built-in templates for a model type
     */
    private function getBuiltInTemplates(string $modelType): Collection
    {
        $templates = [
            'Company' => [
                [
                    'id' => 'company_basic',
                    'name' => 'Basic Company Export',
                    'description' => 'Essential company information',
                    'fields' => ['id', 'name', 'email', 'phone', 'website', 'industry', 'created_at'],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
                [
                    'id' => 'company_detailed',
                    'name' => 'Detailed Company Export',
                    'description' => 'Complete company information with relationships',
                    'fields' => [
                        'id', 'name', 'email', 'phone', 'website', 'industry', 'size',
                        'annual_revenue', 'description', 'account_owner.name', 'people_count',
                        'opportunities_count', 'created_at', 'updated_at',
                    ],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
                [
                    'id' => 'company_sales',
                    'name' => 'Sales-Focused Company Export',
                    'description' => 'Company data optimized for sales analysis',
                    'fields' => [
                        'name', 'industry', 'size', 'annual_revenue', 'account_owner.name',
                        'opportunities_count', 'phone', 'email', 'website',
                    ],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
            ],
            'People' => [
                [
                    'id' => 'people_basic',
                    'name' => 'Basic Contact Export',
                    'description' => 'Essential contact information',
                    'fields' => ['id', 'full_name', 'email', 'phone', 'job_title', 'company.name', 'created_at'],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
                [
                    'id' => 'people_detailed',
                    'name' => 'Detailed Contact Export',
                    'description' => 'Complete contact information',
                    'fields' => [
                        'id', 'first_name', 'last_name', 'email', 'phone', 'mobile',
                        'job_title', 'department', 'company.name', 'account_owner.name',
                        'created_at', 'updated_at',
                    ],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
            ],
            'Opportunity' => [
                [
                    'id' => 'opportunity_basic',
                    'name' => 'Basic Opportunity Export',
                    'description' => 'Essential opportunity information',
                    'fields' => ['id', 'title', 'value', 'stage', 'probability', 'expected_close_date', 'company.name'],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
                [
                    'id' => 'opportunity_sales_pipeline',
                    'name' => 'Sales Pipeline Export',
                    'description' => 'Opportunity data for pipeline analysis',
                    'fields' => [
                        'title', 'value', 'stage', 'probability', 'expected_close_date',
                        'actual_close_date', 'company.name', 'account_owner.name', 'created_at',
                    ],
                    'format_options' => $this->getDefaultFormatOptions(),
                    'is_custom' => false,
                ],
            ],
        ];

        return collect($templates[$modelType] ?? []);
    }

    /**
     * Get custom templates for a model type (placeholder for future database implementation)
     */
    private function getCustomTemplates(): Collection
    {
        // In a full implementation, this would query the database for custom templates
        // For now, return empty collection
        return collect([]);
    }

    /**
     * Get default format options
     */
    private function getDefaultFormatOptions(): array
    {
        return [
            'date_format' => 'Y-m-d H:i:s',
            'include_headers' => true,
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'null_value' => '',
        ];
    }
}
