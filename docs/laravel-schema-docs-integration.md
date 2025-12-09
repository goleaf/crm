# Laravel Schema Docs Integration

Laravel Schema Docs adds an interactive schema dashboard plus a YAML export of the database structure. Routes are locked behind authenticated CRM middleware, and the YAML lives in storage (not committed).

## Configuration
- Config: `config/laravel-schema-docs.php`
  - `yaml_file`: `storage/app/laravel-schema-docs.yaml`
  - `middleware`: `web`, `crm`, `auth`, `verified` (add `role:super_admin` if you want to restrict further)
  - `redirect_url`: set with `SCHEMA_DOCS_REDIRECT_URL` (default `/`)
  - `show_pages`: toggle with `SHOW_SCHEMA_DOCS` (default enabled, `.env.example` sets it to false)
- Supported drivers: MySQL/MariaDB, PostgreSQL, and SQLite (patched `SchemaExtractor` for multi-driver support).
- System tables excluded by default (`cache`, `sessions`, `jobs`, `migrations`, etc.); adjust `excluded_tables` as needed.

## Usage
1) Enable routes when needed: set `SHOW_SCHEMA_DOCS=true` in the environment.  
2) Generate/update YAML after migrations:  
   ```bash
   php artisan laravelschemadocs:generate
   ```  
   The command reads the live schema and refreshes `storage/app/laravel-schema-docs.yaml`.
3) Access the UI (auth + team context required):  
   - Dashboard: `/laravel-schema-docs`  
   - Table detail: `/laravel-schema-docs/table/{name}`  
   - ERD: `/laravel-schema-docs/erd`
4) Adjust the home link with `SCHEMA_DOCS_REDIRECT_URL` (e.g., `/app`) and tighten middleware if only super admins should view the docs.

## Maintenance
- Re-run the generate command whenever migrations change the schema (or add it to your deployment checklist).  
- Keep the YAML file out of VCS; regenerate in each environment when the schema updates.  
- If pages 404, ensure `SHOW_SCHEMA_DOCS` is enabled and the YAML file exists.
