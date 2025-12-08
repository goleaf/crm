# String word wrap helper

## What changed
- Added `App\Support\Helpers\StringHelper::wordWrap()` to centralize wrapping long strings via Laravel's `Str::wordWrap`, returning safe `HtmlString` output when using HTML breaks.
- Wired the helper into Filament views for company, document, invoice line items, and knowledge tag descriptions so long words wrap cleanly without blowing up layouts.
- Added coverage in `tests/Unit/Support/StringHelperTest.php` to lock in wrapping, escaping, and placeholder behavior.

## Usage
- Basic example: `StringHelper::wordWrap($value, characters: 80, break: '<br>', cutLongWords: true);`
- Use `emptyPlaceholder: null` when you want Filament placeholders to render (e.g., `TextEntry::make(...)->placeholder('—')`).
- When the break string contains `<`, the helper returns an `HtmlString`; input is escaped by default. Pass `escape: false` only for already-sanitized HTML.

## Filament patterns
- For tables and infolists, prefer `break: '<br>'` with `cutLongWords: true` to keep narrow columns readable; combine with `lineClamp()` when needed instead of manual CSS or `wordwrap()`.
- Keep labels translated; rely on placeholders for empty states instead of returning raw strings.

## Reference
- Source article: https://laravel-news.com/laravel-string-wordwrap
