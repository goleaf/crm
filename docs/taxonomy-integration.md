# Laravel Taxonomy Integration

- Package: `aliziodev/laravel-taxonomy` @ `^2.8` (nested sets + polymorphic attachments).
- Config: `config/taxonomy.php` uses numeric morphs, custom model `App\Models\Taxonomy`, team-aware unique slugs, and types: `category`, `tag`, `product_category`, `task_category`, `note_category`, `knowledge_category`, `knowledge_tag`, `document_category`, `document_tag`.
- Schema: migration `2025_05_30_000000_create_taxonomies_tables.php` adds `team_id` FK + composite indexes and a unique slug per team/type; pivot deduped on (`taxonomy_id`, `taxonomable_type`, `taxonomable_id`).
- Model: `App\Models\Taxonomy` extends the package model with `HasTeam`; attachable models use `App\Models\Concerns\HasTaxonomies` (filters attachments to the current team).
- Traits applied: Company, People, Lead, Opportunity, Task, Project, SupportCase, Product, Document, KnowledgeArticle, Note.
- Forms now use taxonomy selectors:
  - Tasks → `task_category`
  - Knowledge Articles → `knowledge_category` + `knowledge_tag`
  - Documents → `document_category` + `document_tag`
  - Products → `product_category`

## Usage

```php
use Aliziodev\LaravelTaxonomy\Enums\TaxonomyType;
use App\Models\Taxonomy;

// Create categories/tags
$category = Taxonomy::create(['name' => 'How-To', 'type' => 'knowledge_category']);
$tag = Taxonomy::create(['name' => 'API', 'type' => TaxonomyType::Tag->value]);

// Attach to a model (team-safe via HasTaxonomies)
$article->attachTaxonomies([$category, $tag]);
$tasks = Task::withTaxonomySlug('urgent', 'task_category')->get();

// Trees & lookups
$tree = Taxonomy::tree('product_category');
$exists = Taxonomy::exists('marketing', 'document_category');
```

## Implementation notes

- Run `php artisan migrate` to create taxonomy tables (auto-loaded by config).
- When adding UI for taxonomies, respect `.kiro/steering/translations.md`—use translation keys for labels and helper text.
- Prefer type-specific keys (e.g., `product_category`, `knowledge_tag`) to keep domain taxonomies isolated per spec in `.kiro/specs`.
- All attachments are filtered to the current team via `HasTaxonomies`; cross-team IDs are ignored.

## Next steps to adopt

1) Replace legacy category/tag inputs with taxonomy pickers per module (products, tasks, notes, knowledge, documents).  
2) Seed baseline taxonomies by team (e.g., default task categories, knowledge categories/tags) and map existing records before cutting over UI.  
3) Add Filament form components/tables using taxonomy trees (hierarchical selects) and apply translations for labels/empty states.  
4) Cover attach/detach flows with tests (type filtering, hierarchy queries, and tenant scoping).
