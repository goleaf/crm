# Filament slug generation

- We centralised slug syncing in `app/Filament/Support/SlugHelper.php` following the Laravel News pattern for live slug updates.
- Base fields (`name`, `title`, etc.) call `->live(onBlur: true)->afterStateUpdated(SlugHelper::updateSlug())`. The helper:
  - Updates the slug when creating a record, or when the slug still matches the previous base value (so manual overrides are preserved).
  - Skips updates if the slug was edited by the user.
  - Accepts an optional lock callback to block edits (used for published knowledge articles).
- Slug inputs keep `slug` validation, stay editable unless locked, and dehydrate normally so `HasUniqueSlug` can enforce per-team uniqueness.
- Current integrations: Product, Product Category, Product Attribute, product taxonomy categories relation manager, Knowledge Category, Knowledge Tag, Knowledge Article (locked when status is published/archived).
- To adopt elsewhere, import `SlugHelper` and reuse `SlugHelper::updateSlug(slugField: 'slug', allowReslugOnEdit: true|false, lockCondition: $closure)`, plus `SlugHelper::isLocked()` for `->disabled()`/`->dehydrated()` when needed.
