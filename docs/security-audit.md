# Security audit integration

## What changed
- Added `config/security.php` to manage security headers, Content Security Policy (CSP), and `security.txt` defaults with env toggles.
- New middleware `App\Http\Middleware\SecurityHeaders` (stacked after the existing Treblle headers) applies CSP, cross-origin headers, and honor the config-driven directives. It is appended to the global middleware stack so Filament and public pages receive the same protections.
- Published `/.well-known/security.txt` via `SecurityTxtController`; contacts default to `mailto:security@<app host>` and can be overridden with `SECURITY_TXT_CONTACTS`.
- Introduced a Filament v4.3+ page **Security Audit** (`App\Filament\Pages\SecurityAudit`) under the Settings group. It surfaces the Top 10 audit checklist from [Laravel News](https://laravel-news.com/top-10-laravel-audit-security-issues), shows current header/CSP/security.txt status, and can run `composer audit --locked --format=json` from the UI.

## Configuration
- Toggle headers: `SECURITY_HEADERS_ENABLED=true` (uses both Treblle defaults in `config/headers.php` and our additional headers in `config/security.php`).
- CSP: `SECURITY_CSP_ENABLED=true`, `SECURITY_CSP_REPORT_ONLY=true` (switch to `false` to enforce). Update `security.csp.directives` to adjust sources; `SECURITY_CSP_REPORT_URI` adds a report-only endpoint.
- security.txt: `SECURITY_TXT_CONTACTS=mailto:security@example.com,https://example.com/security`, `SECURITY_TXT_EXPIRES=<RFC7231 date>`, `SECURITY_TXT_POLICY=<url>`, `SECURITY_TXT_LANGUAGES=en`.

## Filament usage
- Navigation: Settings → Security Audit.
- Permissions: verified email + team owner/admin (same as PHP Insights).
- Actions:
  - **Run dependency audit** → executes `composer audit --locked --format=json` and summarizes advisories.
  - Checklist cards highlight: security headers/CSP/security.txt status, rate limiting hints, and manual items (SRI, validation, authorization, XSS, randomness, secret scanning).

## Routes & middleware
- Public route: `/.well-known/security.txt` (`security.txt` name).
- Middleware stack: `ApplySecurityHeaders` (existing) + `SecurityHeaders` (new) appended globally; Filament inherits the same headers.

## Next steps
- Wire `SECURITY_CSP_REPORT_URI` to a reporting endpoint if you want browser CSP reports.
- Add gitleaks/trufflehog CI jobs to automate secret scanning (item #1 from the article).
- Extend the Security Audit page with NPM audit output if needed.
