<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add userstamps columns (editor_id, deleted_by) to CRM tables.
 *
 * This migration adds tracking columns for:
 * - editor_id: Foreign key to users table, tracks who last edited the record
 * - deleted_by: Foreign key to users table, tracks who soft-deleted the record (only for tables with SoftDeletes)
 *
 * Tables are configured in userstampTables() with a boolean indicating whether
 * the table uses soft deletes (and thus needs the deleted_by column).
 *
 *
 * @see \App\Models\Concerns\HasCreator for the trait that uses these columns
 * @see tests/Unit/Migrations/UserstampsColumnsTest.php for test coverage
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds editor_id and deleted_by columns to all configured tables.
     * Handles SQLite compatibility by dropping/recreating the customers_view.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP VIEW IF EXISTS customers_view');
        }

        $this->dropCustomersView();

        foreach ($this->userstampTables() as $tableName => $usesSoftDeletes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $usesSoftDeletes): void {
                if (! Schema::hasColumn($tableName, 'editor_id')) {
                    $editor = $table->foreignId('editor_id')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    if (Schema::hasColumn($tableName, 'creator_id')) {
                        $editor->after('creator_id');
                    }
                }

                if ($usesSoftDeletes && ! Schema::hasColumn($tableName, 'deleted_by')) {
                    $deletedBy = $table->foreignId('deleted_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $deletedBy->after('deleted_at');
                    }
                }
            });
        }

        $this->createCustomersView();
    }

    /**
     * Reverse the migrations.
     *
     * Removes editor_id and deleted_by columns from all configured tables.
     * Handles SQLite compatibility by dropping/recreating the customers_view.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP VIEW IF EXISTS customers_view');

            return;
        }

        $this->dropCustomersView();

        foreach ($this->userstampTables() as $tableName => $usesSoftDeletes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $usesSoftDeletes): void {
                if (Schema::hasColumn($tableName, 'editor_id')) {
                    $table->dropConstrainedForeignId('editor_id');
                }

                if ($usesSoftDeletes && Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->dropConstrainedForeignId('deleted_by');
                }
            });
        }

        $this->createCustomersView();
    }

    /**
     * Get the list of tables that should have userstamps columns.
     *
     * The boolean value indicates whether the table uses soft deletes:
     * - true: Table has SoftDeletes trait, needs both editor_id and deleted_by
     * - false: Table does not use SoftDeletes, only needs editor_id
     *
     * @return array<string, bool> Table name => uses soft deletes
     */
    private function userstampTables(): array
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

    /**
     * Drop the customers_view if it exists.
     *
     * Required before modifying tables that are part of the view.
     */
    private function dropCustomersView(): void
    {
        DB::statement('DROP VIEW IF EXISTS customers_view');
    }

    /**
     * Create the customers_view combining companies and people.
     *
     * This view provides a unified interface for querying customers
     * regardless of whether they are companies or individual people.
     */
    private function createCustomersView(): void
    {
        if (! $this->shouldManageCustomersView()) {
            return;
        }

        if (! Schema::hasTable('companies') || ! Schema::hasTable('people')) {
            return;
        }

        DB::statement(<<<'SQL'
CREATE VIEW customers_view AS
SELECT 
    CONCAT('company-', companies.id) AS uid,
    companies.id AS entity_id,
    companies.team_id,
    'company' AS type,
    companies.name AS name,
    companies.primary_email AS email,
    companies.phone AS phone,
    companies.created_at AS created_at
FROM companies
UNION ALL
SELECT 
    CONCAT('person-', people.id) AS uid,
    people.id AS entity_id,
    people.team_id,
    'person' AS type,
    people.name AS name,
    people.primary_email AS email,
    COALESCE(people.phone_mobile, people.phone_office, people.phone_home, people.phone_fax) AS phone,
    people.created_at AS created_at
FROM people
SQL);
    }

    /**
     * Determine if the customers_view should be managed.
     *
     * SQLite does not support complex views, so we skip view management
     * when running on SQLite (typically in testing environments).
     *
     * @return bool True if the view should be created/dropped
     */
    private function shouldManageCustomersView(): bool
    {
        return DB::getDriverName() !== 'sqlite';
    }
};
