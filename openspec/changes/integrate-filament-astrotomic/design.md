# Design Notes

- Require `astrotomic/laravel-translatable` config publishing and locale setup; `getTranslatableLocales()` can override per resource.
- Register `FilamentAstrotomicPlugin` on Filament v4 panels; align versions with Filament 4.
- Apply `ResourceTranslatable` on resources and the corresponding `{List/Create/Edit/View}Translatable` traits on pages so translations load in forms and pages consistently.
- Use `TranslatableTabs` for per-locale form inputs; ensure main locale fields enforce required validation and consider `makeNameUsing` when switching from array to plain syntax; support prepend/append tabs as needed.
- Modal actions (table edit, select create/edit) must mutate translatable data via helper methods (e.g., `mutateTranslatableData`, unset translation relation) to avoid incorrect saves.
- Translation-aware searching should use `whereTranslationLike` (or equivalent) when searching text columns (e.g., `translations.title`), and limit displayed translations as needed.
