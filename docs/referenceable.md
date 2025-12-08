## Referenceable Integration

- The app now uses [`eg-mohamed/referenceable`](https://github.com/EG-Mohamed/Referenceable) for document numbers. Package config lives in `config/referenceable.php` with yearly resets, 5-digit sequences, and `team_id` as the default tenant column.
- A dedicated migration (`database/migrations/2026_03_21_000000_create_model_reference_counters_table.php`) seeds the `model_reference_counters` table even if `referenceable:install` was already run locally.
- `App\Models\Concerns\HasReferenceNumbering` primes counters from existing sequences and extracts numeric parts from generated references before saves. Keep this trait beside `HasReference` on models with a `sequence` column.
- Covered models and formats:
  - Orders: `ORD-{YEAR}-{SEQ}` (`number` column, yearly reset, 5 digits)
  - Invoices: `INV-{YEAR}-{SEQ}`
  - Purchase Orders: `PO-{YEAR}-{SEQ}`
  - Purchase Order Receipts: `POR-{YEAR}-{SEQ}` (reference only; no sequence column)
- Counter priming happens inside each model’s `registerNumberIfMissing()`/`registerReferenceIfMissing()` so existing sequences seed the next value without long collision retries. The legacy number generator services now delegate to the same logic.

### Adding references to a new model
1) Add `use HasReference;` (and `HasReferenceNumbering` if you track a `sequence`).  
2) Set `$referenceColumn`, `$referenceStrategy = 'template'`, and template/sequential arrays to match your format.  
3) Call a `register...IfMissing()` method from `creating`/`saving` hooks or an observer to prime counters and capture the sequence.  
4) If you need tenant scoping, set `$referenceUniquenessScope = 'tenant'` and `$referenceTenantColumn = 'team_id'`.

### Backfilling
- Existing records with `number`/`sequence` seed `model_reference_counters` the next time `register...IfMissing()` runs (e.g., during an update or via a small Artisan script).  
- Number generator services remain available and now produce values via Referenceable so external callers stay in sync.
