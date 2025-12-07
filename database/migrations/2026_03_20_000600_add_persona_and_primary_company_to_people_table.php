<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the view temporarily (SQLite requires this when altering tables used in views)
        DB::statement('DROP VIEW IF EXISTS customers_view');

        Schema::table('people', function (Blueprint $table): void {
            $table->foreignId('persona_id')
                ->nullable()
                ->after('company_id')
                ->constrained('contact_personas')
                ->nullOnDelete();

            $table->foreignId('primary_company_id')
                ->nullable()
                ->after('persona_id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        // Recreate the view
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

    public function down(): void
    {
        // Drop the view temporarily
        DB::statement('DROP VIEW IF EXISTS customers_view');

        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['persona_id']);
            $table->dropForeign(['primary_company_id']);
            $table->dropColumn(['persona_id', 'primary_company_id']);
        });

        // Recreate the view
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
};
