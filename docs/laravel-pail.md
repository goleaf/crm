# Laravel Pail

`laravel/pail` is bundled for real-time, driver-agnostic log tailing (stack/single, Sentry, Flare) and satisfies the `.kiro/specs/system-technical` logging requirement for analysis tooling. Pail needs the PHP PCNTL extension.

## CLI usage
- Default tail: `php artisan pail`
- Verbose payloads: `php artisan pail -v` (avoids truncation) or `php artisan pail -vv` (adds stack traces)
- Long-running dev tail: `php artisan pail --timeout=0` (already used in `composer dev`)
- Filter by trace/message: `php artisan pail --filter="QueryException"`
- Filter by message only: `php artisan pail --message="User created"`
- Filter by level: `php artisan pail --level=error`
- Filter by authenticated user: `php artisan pail --user=1` (alias `--auth`)

## Filament v4.3+ integration
- New page: `Settings > Log streaming` (`App\Filament\Pages\PailLogs`) shows PCNTL readiness plus curated commands/filters.
- Access: verified tenant owners/admins (same gate as other dev tools).
- Use the preset commands/filters from the page to keep tenant debugging consistent with CLI docs.

## Developer workflow
- `composer dev` runs `php artisan pail --timeout=0` alongside the dev server/queue listener to keep logs streaming.
- Works without extra config across local file logs and external drivers (Sentry/Flare).
- Prefer Pail over `tail -f storage/logs/laravel.log` so filtered streams stay in sync with the active logging stack.
