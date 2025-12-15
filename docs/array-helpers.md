# Array helper integration

## What changed
- Added `App\Support\Helpers\ArrayHelper`, a thin wrapper around Laravel's `Arr` helpers to normalize array data (arrays, collections, JSON strings) before display or export.
- Replaced manual `implode()` formatting in People, Calendar Events, Feature Flags, and duplicate-detection notifications with `ArrayHelper::joinList()` so Filament v4.3+ schemas render JSON/array fields safely.
- Added unit coverage in `tests/Unit/Support/ArrayHelperTest.php` to lock behavior for `joinList`, `keyBy`, `pluck`, `first`, `last`, and `get`.

## Helper usage

- `joinList(mixed $value, string $separator = ', ', ?string $finalSeparator = null, ?string $emptyPlaceholder = '—', bool $trimStrings = true): ?string`
  - Accepts arrays, collections, or JSON strings.
  - Filters empty/blank items and trims strings by default.
  - Use `emptyPlaceholder: null` when the consumer should hide empty states (e.g., `TextEntry`/`ExportColumn`).
  - Example (Filament table/infolist/export):
    ```php
    TextColumn::make('segments')
        ->formatStateUsing(fn (mixed $state): ?string => ArrayHelper::joinList($state, ', ', emptyPlaceholder: null));
    ```
    ```php
    TextEntry::make('attendees')
        ->formatStateUsing(fn (mixed $state): string => ArrayHelper::joinList(ArrayHelper::pluck($state, 'name')) ?? '—');
    ```
    ```php
    $message = ArrayHelper::joinList($lines, PHP_EOL, emptyPlaceholder: '');
    ```
- `keyBy(array $items, string $key): array` – one-line keyed arrays for duplicate lookups or quick indexing.
- `pluck(array $items, string|array $value, ?string $key = null): array` – dot-notation extraction (used for attendee names).
- `first/last(array $items, ?callable $callback = null, mixed $default = null): mixed` – expressive conditional picks instead of manual loops.
- `get(array $items, string|int|null $key, mixed $default = null): mixed` – safe nested access with defaults.

## Filament v4.3+ patterns
- Keep `formatStateUsing(fn (mixed $state) => ...)` callbacks; `joinList` already handles JSON strings vs. arrays to satisfy the v4 JSON-field rule in `.kiro/steering/filament-conventions.md`.
- Use `emptyPlaceholder: null` when the component should hide empty values (e.g., badges/exports) and rely on defaults (`'—'`) when the UI expects a placeholder.
- Prefer `ArrayHelper::joinList()` over `implode()` to avoid locale/spacing bugs and to reuse trimming/normalization across tables, infolists, and exports.

## Reference
- Source article: https://laravel-news.com/laravel-array-helpers-every-developer-should-know-about
- Related steering: `.kiro/steering/laravel-conventions.md`, `.kiro/steering/filament-conventions.md`
