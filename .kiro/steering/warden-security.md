---
inclusion: always
---

# Warden Security Audit Integration

## Core Principles
- Warden (`dgtlss/warden`) provides automated `composer audit` execution with notifications via email, Slack, Discord, and Teams.
- Scheduled audits run daily at 3 AM by default; adjust via `WARDEN_SCHEDULE_FREQUENCY` (hourly/daily/weekly/monthly) and `WARDEN_SCHEDULE_TIME`.
- Audit results are cached (1-hour TTL) to prevent rate limiting; use `skipCache: true` parameter to force fresh audits.
- Custom audits implement `Dgtlss\Warden\Contracts\CustomAudit` interface and register in `config/warden.php`.

## Filament Integration
- Security audit page at `app/Filament/Pages/SecurityAudit.php` displays vulnerability status, packages audited, and last audit time.
- Security status widget (`app/Filament/Widgets/SecurityStatusWidget.php`) shows real-time security metrics on dashboard.
- Access control via `view_security_audit` permission; gate in `canAccess()` and `canView()` methods.
- Use `WardenService` singleton for programmatic access: `app(WardenService::class)->runAudit()`.

## Configuration
- Enable scheduling: `WARDEN_SCHEDULE_ENABLED=true`
- Configure notifications: `WARDEN_EMAIL_RECIPIENTS`, `WARDEN_SLACK_WEBHOOK_URL`, etc.
- Set severity filter: `WARDEN_SEVERITY_FILTER=medium` (low/medium/high/critical)
- Enable audit history: `WARDEN_HISTORY_ENABLED=true` with `WARDEN_HISTORY_RETENTION_DAYS=90`
- Adjust cache: `WARDEN_CACHE_ENABLED=true`, `WARDEN_CACHE_DURATION=3600`, `WARDEN_CACHE_DRIVER=redis`

## Custom Audits
- `EnvironmentSecurityAudit` checks for debug mode in production, weak app keys, missing HTTPS, insecure sessions, empty DB passwords, and log mail drivers.
- Register custom audits in `config/warden.php` under `custom_audits` array.
- Implement `run()`, `getName()`, and `getDescription()` methods.
- Return `AuditResult` with `passed`, `issues`, and `metadata` properties.

## CI/CD Integration
- Run audits in GitHub Actions/GitLab CI with `php artisan warden:audit --junit` for JUnit XML output.
- Store results as artifacts for historical tracking.
- Fail builds on critical vulnerabilities by checking exit code.
- Schedule daily audits in CI pipelines for continuous monitoring.

## Testing
- Feature tests in `tests/Feature/Security/WardenAuditTest.php` cover command execution, caching, severity filtering, and result access.
- Unit tests in `tests/Unit/Audits/EnvironmentSecurityAuditTest.php` validate custom audit logic for each security check.
- Use `Cache::fake()` to test caching behavior without Redis.
- Mock `WardenService` in unit tests to avoid external API calls.

## Translations
- Navigation: `__('app.navigation.security_audit')`, `__('app.navigation.system')`
- Labels: `__('app.labels.security_status')`, `__('app.labels.vulnerabilities')`, `__('app.labels.packages_audited')`
- Actions: `__('app.actions.run_audit')`, `__('app.actions.view_history')`, `__('app.actions.view_details')`
- Notifications: `__('app.notifications.vulnerabilities_found')`, `__('app.notifications.no_vulnerabilities')`
- Messages: `__('app.messages.run_composer_update')`, `__('app.messages.enable_automated_audits')`

## Best Practices
- ✅ Enable scheduled audits in production environments
- ✅ Configure multiple notification channels for redundancy
- ✅ Set appropriate severity filters to reduce noise
- ✅ Enable audit history for compliance tracking
- ✅ Create custom audits for project-specific security checks
- ✅ Integrate with CI/CD pipelines for pre-deployment checks
- ✅ Monitor audit results via Filament dashboard widget
- ✅ Test custom audits thoroughly with unit tests
- ❌ Don't disable caching in production (causes rate limiting)
- ❌ Don't ignore audit notifications
- ❌ Don't skip audits before deployments
- ❌ Don't use overly aggressive scheduling (hourly in production)
- ❌ Don't expose audit results publicly
- ❌ Don't disable audits without security team approval

## Performance
- Audit results cached for 1 hour by default; adjust via `WARDEN_CACHE_DURATION`.
- Parallel execution enabled by default; disable via `WARDEN_PARALLEL_EXECUTION=false` if needed.
- Timeout set to 300 seconds; increase for large projects via `WARDEN_AUDIT_TIMEOUT`.
- Retry logic with 3 attempts and 1-second delay; adjust via `WARDEN_RETRY_ATTEMPTS` and `WARDEN_RETRY_DELAY`.

## Related Documentation
- `docs/warden-security-audit.md` - Comprehensive integration guide
- `docs/security-headers.md` - Security headers configuration
- `docs/rector-v2-integration.md` - Code quality automation
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/laravel-conventions.md` - Laravel best practices

## Integration Points
- Works alongside `treblle/security-headers` for comprehensive security
- Complements Rector v2 code quality checks
- Integrates with PHPStan static analysis
- Runs alongside Pest test suite
- Displays in Filament v4.3+ admin panel
