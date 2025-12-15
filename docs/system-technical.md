# System & Technical

This document summarizes the operational and platform-level controls that keep the CRM reliable, observable, and recoverable.

## Performance & optimization

- Performance guardrails live in `config/performance.php` and are applied via `App\Providers\PerformanceServiceProvider`.
- Pagination bounds are enforced by `App\Http\Middleware\EnforcePaginationLimits` (registered globally in `bootstrap/app.php`).
- Slow queries are logged to `storage/logs/slow-queries*.log` when `PERFORMANCE_SLOW_QUERY_THRESHOLD_MS` is set.
- Database optimization status/migration publishing is available in the Filament page `App\Filament\Pages\DatabaseOptimization`.

See: `docs/performance-safeguards.md`

## Logging & debugging

### Log channels

Targeted channels are defined in `config/logging.php`:

- `system` → `storage/logs/system*.log`
- `auth` → `storage/logs/auth*.log`
- `api` → `storage/logs/api*.log`
- `imports` → `storage/logs/imports*.log`
- `exports` → `storage/logs/exports*.log`
- `workflow` → `storage/logs/workflow*.log`
- `slow_queries` → `storage/logs/slow-queries*.log`
- `backups` → `storage/logs/backups*.log`
- `email` → `storage/logs/email*.log`
- `email_subscriptions_channel` → `storage/logs/email-subscriptions*.log` (legacy compatibility)

### Pail (log tailing)

- CLI: `php artisan pail` (PCNTL recommended locally)
- Filament: `App\Filament\Pages\PailLogs` provides curated commands/filters.

See: `docs/laravel-pail.md`

## Security controls

### Security headers / CSP / security.txt

- Treblle baseline security headers: `App\Http\Middleware\ApplySecurityHeaders` + `config/headers.php`
- CSP and additional headers: `App\Http\Middleware\SecurityHeaders` + `config/security.php`
- Public `security.txt`: `/.well-known/security.txt` via `App\Http\Controllers\SecurityTxtController`

See: `docs/security-headers.md`, `docs/security-audit.md`

### Dependency vulnerability scanning

- Warden (`dgtlss/warden`) runs `composer audit` on a schedule and can notify multiple channels.
- Filament page: `App\Filament\Pages\SecurityAudit`

See: `docs/warden-security-audit.md`

### IP allow/deny lists (Filament panels)

IP-based access control is enforced for Filament panels via `App\Http\Middleware\EnforceIpLists`:

- Allowlist: `CRM_IP_WHITELIST` (comma-separated IPs/CIDRs)
- Denylist: `CRM_IP_DENYLIST` (comma-separated IPs/CIDRs)

If the allowlist is non-empty, only matching IPs may access the panel. Denylist always wins.

### File upload restrictions

Upload limits are centrally configured in `config/laravel-crm.php`:

- `uploads.max_file_size` (KB)
- `uploads.allowed_extensions.{documents|images|archives}`

Filament forms should apply constraints via `App\Filament\Support\UploadConstraints::apply($upload, types: [...])`.

## Backup & recovery

### Commands

- Create backup: `php artisan backup:create`
- Cleanup expired backups: `php artisan backup:cleanup`
- Restore backup: `php artisan backup:restore`

### Scheduling

Backup scheduling is configured in `bootstrap/app.php` (full + incremental + cleanup).

### Storage & UI

- Backup files are written under `storage/app/backups/`.
- Filament resource: `App\Filament\Resources\BackupJobResource` (download/restore actions).

