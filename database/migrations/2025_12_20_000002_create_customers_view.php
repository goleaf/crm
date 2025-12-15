<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite struggles with views during table alters; skip creating the view in tests.
            return;
        }

        DB::statement('DROP VIEW IF EXISTS customers_view');

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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS customers_view');
    }
};
