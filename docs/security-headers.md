# Security Headers

**Date:** 2026-07-16  
**Component:** HTTP response security (web/API/Filament)  
**Package:** `treblle/security-headers`

## What changed
- Installed the Treblle security headers middleware set and added `App\Http\Middleware\ApplySecurityHeaders` to the global stack so every response removes `Server`/`X-Powered-By`, applies `Referrer-Policy`, `Permissions-Policy`, `Expect-CT`, and `X-Content-Type-Options`, and emits HSTS only when the request is HTTPS.
- Published `config/headers.php` with environment-driven defaults and an `except` list to exclude specific paths (e.g., `/up`) without editing middleware.
- Wired HSTS to respect `SECURITY_HEADERS_ONLY_HTTPS`, preventing accidental preload on local HTTP while keeping it enforced in production.

## Configuration
- Toggle and scope:
  - `SECURITY_HEADERS_ENABLED` (default: `true`)
  - `SECURITY_HEADERS_ONLY_HTTPS` (default: `true`, skips HSTS on non-HTTPS requests)
  - `headers.except` array in `config/headers.php` for path patterns to skip (empty by default)
- Header values:
  - `SECURITY_HEADERS_REFERRER_POLICY` (default: `no-referrer-when-downgrade`)
  - `SECURITY_HEADERS_HSTS` (default: `max-age=31536000; includeSubDomains`)
  - `SECURITY_HEADERS_EXPECT_CT` (default: `enforce, max-age=30`)
  - `SECURITY_HEADERS_PERMISSIONS_POLICY` (default: restrictive baseline for sensors/media/fullscreen/sync-xhr)
  - `SECURITY_HEADERS_CONTENT_TYPE_OPTIONS` (default: `nosniff`)
- Update values via env and run `php artisan optimize:clear` (or rely on the next deploy) to refresh cached config.

## Operational notes
- The middleware is global, so Filament v4 panels, CRM routes, and API endpoints all receive the same headers without per-route wiring.
- Prefer toggling via env/config rather than removing `ApplySecurityHeaders` from `bootstrap/app.php`; use `headers.except` for diagnostics endpoints that need a different policy.
- If you later introduce CSP, add nonces to inline scripts/partials and update the permissions policy to cover any new APIs you enable.
