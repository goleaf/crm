# Pest Laravel Expectations

**Stack:** Laravel 12 | Filament 4.3+ | Pest 4  
**Package:** `defstudio/pest-plugin-laravel-expectations` (dev-only)  
**Docs:** https://docs.defstudio.it/pest-plugin-laravel-expectations/

## What it adds
- HTTP/response expectations: `toBeOk()`, `toBeRedirect($url)`, `toHaveJson()`, `toHaveInvalid()`, `toHaveHeader()`, `toBeDownload()`.
- Model/database expectations: `toExist()`, `toBeDeleted()`, `toBeSoftDeleted()`, `toBelongTo($parent)`, `toOwn($child)`, `toBeInDatabase()`.
- Auth/collections/time/storage helpers: `toBeAbleTo()`, `toBeAuthenticated()`, `toBeCollection()`, `toBeAfter()/toBeBetween()`, `toExistInStorage()`.

Expectations are autoloaded—no manual bootstrap needed beyond requiring the package.

## Usage patterns for this CRM
- Prefer expectations over PHPUnit asserts for readability and parity with Filament/Pest style:
  - `expect($response)->toBeOk()->toContainText('Dashboard');`
  - `expect($response)->toBeRedirect(route('login'))->toHaveSession('errors');`
  - `expect($user)->toBeAbleTo('view', $record);`
  - `expect($record)->toExist()->toBelongTo($team);`
  - `expect(Storage::disk('public')->path($file))->toExistInStorage();`
- Combine with Livewire/Filament tests:
  - `livewire(ListCompanies::class)->assertCanSeeTableRecords($companies);`
  - `expect(get(route('filament.app.pages.dashboard')))->toBeRedirect();` (ensures tenants are enforced)
  - After actions that redirect: `expect($this->get(CompanyResource::getUrl('index')))->toBeRedirect();`
- Use response text helpers to avoid brittle HTML assertions: `toRenderText()` / `toContainTextInOrder()`.

## When to reach for it
- HTTP entry points (auth redirects, throttling, downloads, JSON APIs).
- Policy checks (`toBeAbleTo`) instead of manual `assertTrue($user->can(...))`.
- Model lifecycle coverage (existence, soft deletes) without raw DB queries.
- Storage and time-sensitive flows (reminders, calendar exports) with `toExistInStorage()` / `toBeBetween()`.

## Notes
- Keep expectations chained for clarity; mix with existing `assertAuthenticated*` or Livewire assertions where needed.
- For validation failures prefer `toHaveInvalid()`; for JSON validation use `toHaveJsonValidationErrors()`.
- See `tests/Feature/Public/PublicPagesTest.php` and `tests/Feature/Auth/SocialiteLoginTest.php` for examples.
