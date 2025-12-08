# Notable package integration

## What changed
- Installed `eg-mohamed/notable` and published config to `config/notable.php` (adds `team_column` so notes stay tenant-scoped).
- Added migration `2025_12_07_213855_create_notable_table.php` to create the `notables` table with `team_id`, morph columns for `notable`/`creator`, and timestamps.
- Introduced `App\Models\NotableEntry` (extends the vendor model + `HasTeam`) and a safe wrapper trait `App\Models\Concerns\HasNotableEntries` to avoid clashing with the existing `HasNotes` API; `HasNotesAndNotables` composes both for models that already used notes.
- Applied the combined trait to all note-enabled domain models (companies, people, opportunities, leads, tasks, quotes, orders, deliveries, support cases, projects) so they can emit lightweight text notes.

## Runtime API
- `addNotableNote(string $note, ?Model $creator = null)`: creates a `NotableEntry`, copying the owning model’s `team_id` and optional creator morph.
- `notableNotes()`, `latestNotableNote()`, `hasNotableNotes()`, `notableNotesCount()`: accessors for the ordered `notables()` relation.
- Query helpers: `notableNotesByCreator()`, `notableNotesWithCreator()`, `searchNotableNotes()`, `notableNotesToday()/ThisWeek()/ThisMonth()`, `notableNotesInRange()`, `updateNotableNote()`, `deleteNotableNote()`.
- Relation: `notables()` is a `MorphMany<NotableEntry>` ordered by `config('notable.order_by_*')`.

## Usage
```php
$company = Company::first();
$user = auth()->user();

$company->addNotableNote('Followed up with onboarding issues', $user);

$recent = $company->notableNotesThisWeek();
$latest = $company->latestNotableNote()?->note;
$search = $company->searchNotableNotes('onboarding');
```

## When to use
- Use `HasNotableEntries` for quick, plain-text audit trails.
- Keep using `HasNotes` + `Note` for rich notes (templates, visibility, attachments, categories).

## Migration & validation
- Run `php artisan migrate` to create the `notables` table in existing environments.
- Tests: `tests/Unit/Models/Concerns/HasNotableEntriesTest.php` exercises creation, ordering, search, and date-scoped helpers.
