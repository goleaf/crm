<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
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
     * @return array<string, bool>
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
            'support_cases' => true,
            'task_templates' => false,
            'tasks' => true,
        ];
    }

    private function dropCustomersView(): void
    {
        DB::statement('DROP VIEW IF EXISTS customers_view');
    }

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

    private function shouldManageCustomersView(): bool
    {
        return DB::getDriverName() !== 'sqlite';
    }
};
