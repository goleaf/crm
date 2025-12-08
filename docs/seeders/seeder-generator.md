# Seeder Generator

**Package:** `tyghaykal/laravel-seed-generator` (dev)  
**Filament Page:** `app/Filament/Pages/SeederGenerator.php`  
**Service:** `app/Services/SeederGeneratorService.php`  
**Last Updated:** July 16, 2025

Generate Laravel seeders from live database data directly in Filament. The generator uses the official `seed:generate` command to export models or tables into `database/seeders` with optional filters and relation controls.

## How to use (Filament)
1. Open **Settings → Seeder Generator** in the Filament sidebar (admin/owner only).
2. Choose **Mode**:
   - **Model mode:** pick one or more models (auto-discovered under `App\Models`).
   - **Table mode:** pick one or more tables from the current connection.
3. Configure options:
   - **Relations:** toggle inclusion, list relation names, and set a relation limit.
   - **Filters:** IDs include/exclude, fields include/exclude, simple where clauses, where-in/where-not-in, limit, and ordering.
   - **Output:** optional sub-path for the seeder, toggle whether to register it in `DatabaseSeeder`, and review the command preview.
4. Click **Generate Seeder**. Success/error output is shown via Filament notifications.

## CLI reference
- Model mode:  
  `php artisan seed:generate --model-mode --models=Lead --relations=tasks,notes --limit=50 --order-by=created_at,desc`
- Table mode:  
  `php artisan seed:generate --table-mode --tables=users,teams --where=status,=,active --output=Exports/Core`
- Skip registration in `DatabaseSeeder`: add `--no-seed`.

## Implementation notes
- `SeederGeneratorService` normalizes options (relations, where/where-in clauses, ordering) and surfaces model/table options without Doctrine DBAL.
- The Filament page requires a verified user who is an owner or admin of the active tenant team; it uses translation keys for all labels and shows a live command preview.
