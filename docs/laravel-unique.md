# Laravel Unique package integration

## What changed
- Installed `willvincent/laravel-unique` (@ ^1.2) and published `config/unique_names.php` with soft-delete aware checks, trimmed inputs, and higher attempt limits.
- Added a reusable `App\Models\Concerns\HasUniqueSlug` trait that slugifies source fields, falls back to the current team when needed, and delegates uniqueness to the package before every save.
- Applied the trait to slugged domain models: Account, Project, Product, ProductCategory, ProductAttribute, KnowledgeCategory, KnowledgeTag, KnowledgeArticle, ProcessDefinition (and thus WorkflowDefinition).
- Removed bespoke slug generators/observers; uniqueness now comes from the package with `-{n}` suffixing for slug fields.

## Trait usage
```php
use App\Models\Concerns\HasUniqueSlug;

class Product extends Model
{
    use HasUniqueSlug;

    // Optional overrides
    protected string $uniqueBaseField = 'title'; // defaults to 'name'
    protected array $constraintFields = [];      // defaults to ['team_id']
    protected string $uniqueSuffixFormat = '-{n}';
}
```
- When the base field changes and the slug is untouched, the slug is refreshed from that field and deduplicated before save.
- Team-aware models fall back to `CurrentTeamResolver` so constraint fields are populated even if `team_id` is not explicitly set in form data.
- Soft-deleted rows are included in uniqueness checks to mirror database unique indexes.

## Configuration
- `unique_field` / `constraint_fields`: global defaults; models can override per-field.
- `suffix_format`: defaults to `' ({n})'`; slug trait sets `- {n}` via `$uniqueSuffixFormat`.
- `soft_delete` / `with_trashed`: enabled to prevent collisions with trashed records.
- `max_attempts` / `max_tries`: set to 25 to give the package room to re-suffix.

## Notes for Filament v4.3+
- Existing slug inputs continue to work; leaving the slug blank will auto-slugify from the source field and append `-1`, `-2`, … when needed.
- No additional validation rules are required—the model layer now guards uniqueness consistently across create/edit actions and relation managers.
