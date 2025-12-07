# Import/Export

Status: Partial

Coverage:
- Done: 1 (exports)
- Partial: 7 (imports, mapping, validation, dedupe, previews, history, templates)
- Missing: 0 additional modules

Details
- Exporters exist for companies, people, leads, opportunities, support cases, and notes (`app/Filament/Exports/*Exporter.php`) and are wired into resource bulk actions.
- Filament import model is stubbed with team scoping (`app/Models/Import.php`), but no resource exposes import actions.

Gaps
- No import wizard for CSV/XLSX/vCard, no field mapping or validation preview, and no duplicate detection/merge flows.
- No import/export history UI or template management.

Source: docs/suitecrm-features.md (Import/Export)
