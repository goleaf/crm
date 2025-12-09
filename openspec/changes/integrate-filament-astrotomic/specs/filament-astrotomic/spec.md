# Filament Astrotomic Integration

## ADDED Requirements

#### Requirement 1: Configure translatable locales.
- Projects shall install `astrotomic/laravel-translatable`, publish its config, and set the locale list; resources may override locales via `getTranslatableLocales()`.
#### Scenario: Set locales for translations
- Given the translatable config is published
- When locales are set to `['uk', 'en']`
- Then those locales are available across translatable resources unless overridden in a specific resource

#### Requirement 2: Register the Filament Astrotomic plugin on panels.
- Filament v4 panels shall include `FilamentAstrotomicPlugin::make()` in the `plugins()` configuration to enable the integration.
#### Scenario: Add plugin to a panel
- Given a Filament admin panel configuration
- When the plugin is added to `plugins()`
- Then translatable features become available to resources/pages using the provided traits

#### Requirement 3: Apply translatable traits on resources and pages.
- Translatable resources shall use `ResourceTranslatable`, and their List/Create/Edit/View pages shall use the corresponding `{Type}Translatable` traits.
#### Scenario: Make a resource and pages translatable
- Given a `CourseResource` with list/create/edit/view pages
- When `ResourceTranslatable` is applied to the resource and the page traits are added
- Then each page loads and saves translations for all configured locales

#### Requirement 4: Use translatable tabs for per-locale form inputs.
- Forms shall use `TranslatableTabs` to render locale-specific inputs, with main-locale-required rules, and support prepend/append tabs and alternate naming (`makeNameUsing`/plain syntax).
#### Scenario: Render per-locale tabs with required main locale
- Given a form using `TranslatableTabs` with title/content fields
- When the main locale tab is required for title/content
- Then each locale tab renders its inputs, and only the main locale enforces required validation

#### Requirement 5: Support modal forms for translatable data.
- Modal-based actions (e.g., table EditAction, select create/edit option modals) shall mutate translatable data via helper methods and unset translation relations to avoid incorrect saves.
#### Scenario: Edit record via table action modal
- Given an Edit table action uses translatable fields
- When the action mutates record data with `mutateTranslatableData` and unsets the `translation` relation before save
- Then the translatable fields persist correctly per locale

#### Requirement 6: Enable translation-aware search and columns.
- Columns referencing translations (e.g., `translations.title`) shall use translation-aware search queries (e.g., `whereTranslationLike`) and may limit displayed translations (e.g., `limitList(1)`).
#### Scenario: Search by translated title
- Given a table column for `translations.title`
- When a user searches for a keyword
- Then the query searches translated values via `whereTranslationLike` and returns matching translated records
