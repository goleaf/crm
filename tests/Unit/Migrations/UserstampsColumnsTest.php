<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Tests for Userstamps Columns Migration
 *
 * Verifies that editor_id and deleted_by columns are properly added
 * to all tables that support userstamps functionality.
 */
describe('Userstamps Columns Migration', function (): void {
    /**
     * Tables that should have editor_id column.
     *
     * @return array<string, bool>
     */
    function userstampTables(): array
    {
        return [
            'calendar_events' => true,
            'companies' => true,
            'company_revenues' => false,
            'document_templates' => false,
            'documents' => true,
            'invoices' => true,
            'knowledge_articles' => true,
            'knowledge_categories' => true,
            'knowledge_faqs' => true,
            'knowledge_tags' => true,
            'knowledge_template_responses' => true,
            'leads' => true,
            'notes' => true,
            'opportunities' => true,
            'orders' => true,
            'pdf_templates' => true,
            'people' => true,
            'projects' => true,
            'purchase_orders' => true,
            'quotes' => true,
            'cases' => true,
            'task_templates' => false,
            'tasks' => true,
        ];
    }

    it('adds editor_id column to all userstamp tables', function (): void {
        foreach (array_keys(userstampTables()) as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            expect(Schema::hasColumn($tableName, 'editor_id'))
                ->toBeTrue("Table {$tableName} should have editor_id column");
        }
    });

    it('adds deleted_by column to tables with soft deletes', function (): void {
        foreach (userstampTables() as $tableName => $usesSoftDeletes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if ($usesSoftDeletes) {
                expect(Schema::hasColumn($tableName, 'deleted_by'))
                    ->toBeTrue("Table {$tableName} should have deleted_by column");
            }
        }
    });

    it('does not add deleted_by column to tables without soft deletes', function (): void {
        $tablesWithoutSoftDeletes = array_filter(
            userstampTables(),
            fn (bool $usesSoftDeletes): bool => ! $usesSoftDeletes,
        );

        foreach (array_keys($tablesWithoutSoftDeletes) as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            // These tables should NOT have deleted_by column
            expect(Schema::hasColumn($tableName, 'deleted_by'))
                ->toBeFalse("Table {$tableName} should NOT have deleted_by column");
        }
    });

    it('verifies cases table exists and has correct columns', function (): void {
        expect(Schema::hasTable('cases'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'creator_id'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'deleted_at'))->toBeTrue();
    });

    it('verifies companies table has userstamp columns', function (): void {
        expect(Schema::hasTable('companies'))->toBeTrue();
        expect(Schema::hasColumn('companies', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('companies', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('companies', 'creator_id'))->toBeTrue();
    });

    it('verifies people table has userstamp columns', function (): void {
        expect(Schema::hasTable('people'))->toBeTrue();
        expect(Schema::hasColumn('people', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('people', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('people', 'creator_id'))->toBeTrue();
    });

    it('verifies tasks table has userstamp columns', function (): void {
        expect(Schema::hasTable('tasks'))->toBeTrue();
        expect(Schema::hasColumn('tasks', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('tasks', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('tasks', 'creator_id'))->toBeTrue();
    });

    it('verifies opportunities table has userstamp columns', function (): void {
        expect(Schema::hasTable('opportunities'))->toBeTrue();
        expect(Schema::hasColumn('opportunities', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('opportunities', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('opportunities', 'creator_id'))->toBeTrue();
    });

    it('verifies leads table has userstamp columns', function (): void {
        expect(Schema::hasTable('leads'))->toBeTrue();
        expect(Schema::hasColumn('leads', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('leads', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('leads', 'creator_id'))->toBeTrue();
    });

    it('verifies notes table has userstamp columns', function (): void {
        expect(Schema::hasTable('notes'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'creator_id'))->toBeTrue();
    });

    it('verifies projects table has userstamp columns', function (): void {
        expect(Schema::hasTable('projects'))->toBeTrue();
        expect(Schema::hasColumn('projects', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('projects', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('projects', 'creator_id'))->toBeTrue();
    });

    it('verifies invoices table has userstamp columns', function (): void {
        expect(Schema::hasTable('invoices'))->toBeTrue();
        expect(Schema::hasColumn('invoices', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('invoices', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('invoices', 'creator_id'))->toBeTrue();
    });

    it('verifies quotes table has userstamp columns', function (): void {
        expect(Schema::hasTable('quotes'))->toBeTrue();
        expect(Schema::hasColumn('quotes', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('quotes', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('quotes', 'creator_id'))->toBeTrue();
    });

    it('verifies purchase_orders table has userstamp columns', function (): void {
        expect(Schema::hasTable('purchase_orders'))->toBeTrue();
        expect(Schema::hasColumn('purchase_orders', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('purchase_orders', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('purchase_orders', 'creator_id'))->toBeTrue();
    });

    it('verifies orders table has userstamp columns', function (): void {
        expect(Schema::hasTable('orders'))->toBeTrue();
        expect(Schema::hasColumn('orders', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('orders', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('orders', 'creator_id'))->toBeTrue();
    });

    it('verifies calendar_events table has userstamp columns', function (): void {
        expect(Schema::hasTable('calendar_events'))->toBeTrue();
        expect(Schema::hasColumn('calendar_events', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('calendar_events', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('calendar_events', 'creator_id'))->toBeTrue();
    });

    it('verifies documents table has userstamp columns', function (): void {
        expect(Schema::hasTable('documents'))->toBeTrue();
        expect(Schema::hasColumn('documents', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('documents', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('documents', 'creator_id'))->toBeTrue();
    });

    it('verifies pdf_templates table has userstamp columns', function (): void {
        expect(Schema::hasTable('pdf_templates'))->toBeTrue();
        expect(Schema::hasColumn('pdf_templates', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('pdf_templates', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('pdf_templates', 'creator_id'))->toBeTrue();
    });

    it('verifies knowledge_articles table has userstamp columns', function (): void {
        expect(Schema::hasTable('knowledge_articles'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_articles', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_articles', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_articles', 'creator_id'))->toBeTrue();
    });

    it('verifies knowledge_categories table has userstamp columns', function (): void {
        expect(Schema::hasTable('knowledge_categories'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_categories', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_categories', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_categories', 'creator_id'))->toBeTrue();
    });

    it('verifies knowledge_faqs table has userstamp columns', function (): void {
        expect(Schema::hasTable('knowledge_faqs'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_faqs', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_faqs', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_faqs', 'creator_id'))->toBeTrue();
    });

    it('verifies knowledge_tags table has userstamp columns', function (): void {
        expect(Schema::hasTable('knowledge_tags'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_tags', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_tags', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_tags', 'creator_id'))->toBeTrue();
    });

    it('verifies knowledge_template_responses table has userstamp columns', function (): void {
        expect(Schema::hasTable('knowledge_template_responses'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_template_responses', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_template_responses', 'deleted_by'))->toBeTrue();
        expect(Schema::hasColumn('knowledge_template_responses', 'creator_id'))->toBeTrue();
    });

    it('verifies company_revenues table has editor_id but not deleted_by', function (): void {
        if (! Schema::hasTable('company_revenues')) {
            $this->markTestSkipped('company_revenues table does not exist');
        }

        expect(Schema::hasColumn('company_revenues', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('company_revenues', 'deleted_by'))->toBeFalse();
    });

    it('verifies document_templates table has editor_id but not deleted_by', function (): void {
        if (! Schema::hasTable('document_templates')) {
            $this->markTestSkipped('document_templates table does not exist');
        }

        expect(Schema::hasColumn('document_templates', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('document_templates', 'deleted_by'))->toBeFalse();
    });

    it('verifies task_templates table has editor_id but not deleted_by', function (): void {
        if (! Schema::hasTable('task_templates')) {
            $this->markTestSkipped('task_templates table does not exist');
        }

        expect(Schema::hasColumn('task_templates', 'editor_id'))->toBeTrue();
        expect(Schema::hasColumn('task_templates', 'deleted_by'))->toBeFalse();
    });

    it('verifies editor_id columns are nullable', function (): void {
        $tablesToCheck = ['companies', 'people', 'tasks', 'opportunities', 'cases'];

        foreach ($tablesToCheck as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = Schema::getColumns($tableName);
            $editorColumn = collect($columns)->firstWhere('name', 'editor_id');

            expect($editorColumn)->not->toBeNull("editor_id column should exist in {$tableName}");
            expect($editorColumn['nullable'])->toBeTrue("editor_id should be nullable in {$tableName}");
        }
    });

    it('verifies deleted_by columns are nullable', function (): void {
        $tablesToCheck = ['companies', 'people', 'tasks', 'opportunities', 'cases'];

        foreach ($tablesToCheck as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = Schema::getColumns($tableName);
            $deletedByColumn = collect($columns)->firstWhere('name', 'deleted_by');

            expect($deletedByColumn)->not->toBeNull("deleted_by column should exist in {$tableName}");
            expect($deletedByColumn['nullable'])->toBeTrue("deleted_by should be nullable in {$tableName}");
        }
    });

    it('uses correct table name cases instead of support_cases', function (): void {
        // Verify the table is named 'cases' not 'support_cases'
        expect(Schema::hasTable('cases'))->toBeTrue();
        expect(Schema::hasTable('support_cases'))->toBeFalse();
    });
});
