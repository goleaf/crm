<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountMerge;
use App\Models\Company;
use App\Models\Note;
use App\Models\People;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AccountMergeService
{
    /**
     * Merge a duplicate company into a primary company.
     *
     * @param  Company  $primary  The company to keep
     * @param  Company  $duplicate  The company to merge and soft delete
     * @param  array<string, mixed>  $fieldSelections  Field values to use (key => 'primary' or 'duplicate')
     * @return array{success: bool, merge_id: int|null, error: string|null}
     */
    public function merge(Company $primary, Company $duplicate, array $fieldSelections = []): array
    {
        // Validate merge preconditions
        if ($primary->getKey() === $duplicate->getKey()) {
            return [
                'success' => false,
                'merge_id' => null,
                'error' => 'Cannot merge a company with itself',
            ];
        }

        if ($primary->trashed() || $duplicate->trashed()) {
            return [
                'success' => false,
                'merge_id' => null,
                'error' => 'Cannot merge deleted companies',
            ];
        }

        try {
            return DB::transaction(function () use ($primary, $duplicate, $fieldSelections): array {
                // Apply field selections
                $this->applyFieldSelections($primary, $duplicate, $fieldSelections);

                // Transfer relationships
                $transferredRelationships = $this->transferRelationships($primary, $duplicate);

                // Create audit trail
                $accountMerge = AccountMerge::create([
                    'primary_company_id' => $primary->getKey(),
                    'duplicate_company_id' => $duplicate->getKey(),
                    'merged_by_user_id' => auth()->id(),
                    'field_selections' => $fieldSelections,
                    'transferred_relationships' => $transferredRelationships,
                ]);

                // Soft delete the duplicate
                $duplicate->delete();

                return [
                    'success' => true,
                    'merge_id' => $accountMerge->getKey(),
                    'error' => null,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Account merge failed', [
                'primary_id' => $primary->getKey(),
                'duplicate_id' => $duplicate->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'merge_id' => null,
                'error' => 'Merge operation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Preview what a merge would look like without executing it.
     *
     * @return array<string, array{attribute: string, label: string, primary: mixed, duplicate: mixed}>
     */
    public function previewMerge(Company $primary, Company $duplicate): array
    {
        $fields = [
            'name' => 'Company Name',
            'website' => 'Website',
            'industry' => 'Industry',
            'phone' => 'Phone',
            'primary_email' => 'Primary Email',
            'revenue' => 'Annual Revenue',
            'employee_count' => 'Employee Count',
            'description' => 'Description',
            'billing_street' => 'Billing Street',
            'billing_city' => 'Billing City',
            'billing_state' => 'Billing State',
            'billing_postal_code' => 'Billing Postal Code',
            'billing_country' => 'Billing Country',
            'shipping_street' => 'Shipping Street',
            'shipping_city' => 'Shipping City',
            'shipping_state' => 'Shipping State',
            'shipping_postal_code' => 'Shipping Postal Code',
            'shipping_country' => 'Shipping Country',
            'account_type' => 'Account Type',
            'ownership' => 'Ownership',
            'currency_code' => 'Currency Code',
        ];

        $preview = [];

        foreach ($fields as $attribute => $label) {
            $primaryValue = $primary->{$attribute};
            $duplicateValue = $duplicate->{$attribute};

            $preview[$attribute] = [
                'attribute' => $attribute,
                'label' => $label,
                'primary' => $this->formatValue($primaryValue),
                'duplicate' => $this->formatValue($duplicateValue),
            ];
        }

        // Add relationship counts
        $preview['people_count'] = [
            'attribute' => 'people_count',
            'label' => 'Associated People',
            'primary' => $primary->people()->count(),
            'duplicate' => $duplicate->people()->count(),
        ];

        $preview['opportunities_count'] = [
            'attribute' => 'opportunities_count',
            'label' => 'Opportunities',
            'primary' => $primary->opportunities()->count(),
            'duplicate' => $duplicate->opportunities()->count(),
        ];

        $preview['tasks_count'] = [
            'attribute' => 'tasks_count',
            'label' => 'Tasks',
            'primary' => $primary->tasks()->count(),
            'duplicate' => $duplicate->tasks()->count(),
        ];

        $preview['notes_count'] = [
            'attribute' => 'notes_count',
            'label' => 'Notes',
            'primary' => $primary->notes()->count(),
            'duplicate' => $duplicate->notes()->count(),
        ];

        return $preview;
    }

    /**
     * Rollback a merge operation (not fully implemented - would require complex data restoration).
     *
     * @return array{success: bool, error: string|null}
     */
    public function rollback(): array
    {
        // For now, this is a placeholder - full rollback would require:
        // 1. Restoring the soft-deleted duplicate company
        // 2. Reversing all relationship transfers
        // 3. Reverting field changes
        // This is complex and would require storing more detailed state
        return [
            'success' => false,
            'error' => 'Rollback functionality not yet implemented',
        ];
    }

    /**
     * Apply field selections from the merge preview.
     *
     * @param  array<string, string>  $fieldSelections  Map of field => 'primary' or 'duplicate'
     */
    private function applyFieldSelections(Company $primary, Company $duplicate, array $fieldSelections): void
    {
        $fillableFields = [
            'name',
            'website',
            'industry',
            'phone',
            'primary_email',
            'revenue',
            'employee_count',
            'description',
            'billing_street',
            'billing_city',
            'billing_state',
            'billing_postal_code',
            'billing_country',
            'shipping_street',
            'shipping_city',
            'shipping_state',
            'shipping_postal_code',
            'shipping_country',
            'account_type',
            'ownership',
            'currency_code',
        ];

        foreach ($fieldSelections as $field => $source) {
            if (! in_array($field, $fillableFields, true)) {
                continue;
            }

            if ($source === 'duplicate') {
                $value = $duplicate->{$field};
                if ($value !== null && $value !== '') {
                    $primary->{$field} = $value;
                }
            }
            // If source is 'primary', we keep the existing value (no action needed)
        }

        $primary->save();
    }

    /**
     * Transfer all relationships from duplicate to primary.
     *
     * @return array<string, int> Count of transferred items by type
     */
    private function transferRelationships(Company $primary, Company $duplicate): array
    {
        $transferred = [
            'people' => 0,
            'opportunities' => 0,
            'tasks' => 0,
            'notes' => 0,
        ];

        // Transfer people
        $people = $duplicate->people()->get();
        foreach ($people as $person) {
            $person->update(['company_id' => $primary->getKey()]);
            $transferred['people']++;
        }

        // Transfer opportunities
        $opportunities = $duplicate->opportunities()->get();
        foreach ($opportunities as $opportunity) {
            $opportunity->update(['company_id' => $primary->getKey()]);
            $transferred['opportunities']++;
        }

        // Transfer tasks (polymorphic many-to-many)
        $tasks = $duplicate->tasks()->get();
        foreach ($tasks as $task) {
            // Check if task is already attached to primary
            if (! $primary->tasks()->where('task_id', $task->getKey())->exists()) {
                $primary->tasks()->attach($task);
            }
            // Remove from duplicate
            $duplicate->tasks()->detach($task);
            $transferred['tasks']++;
        }

        // Transfer notes (polymorphic many-to-many)
        $notes = $duplicate->notes()->get();
        foreach ($notes as $note) {
            // Check if note is already attached to primary
            if (! $primary->notes()->where('note_id', $note->getKey())->exists()) {
                $primary->notes()->attach($note);
            }
            // Remove from duplicate
            $duplicate->notes()->detach($note);
            $transferred['notes']++;
        }

        return $transferred;
    }

    /**
     * Format a value for display in the merge preview.
     */
    private function formatValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        return $value;
    }
}
