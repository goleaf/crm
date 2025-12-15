# Pest Stressless

**Stack:** Laravel 12 | Filament 4.3+ | Pest 4  
**Package:** `pestphp/pest-plugin-stressless` (dev-only)  
**Docs:** https://pestphp.com/docs/stress-testing

## What it adds
- Command-line stress runner: `./vendor/bin/pest stress <url> --concurrency=5 --duration=10` for fast, ad-hoc probes.
- Expectation-friendly API via `Pest\Stressless\stress()` so you can assert latency, failures, and rates inside Pest tests.
- k6-backed engine (AGPL binary fetched on first run); keep in mind network usage and target approvals.

## How we use it
- Opt-in suite lives in `tests/Stressless` (e.g., `StresslessSmokeTest`) and is **skipped** unless envs are set: `RUN_STRESS_TESTS=1 STRESSLESS_TARGET=https://staging.example.com/filament/app ./vendor/bin/pest --group=stressless`.
- Tune knobs via env: `STRESSLESS_CONCURRENCY` (default 3), `STRESSLESS_DURATION` seconds (default 5), `STRESSLESS_P95_THRESHOLD_MS` (default 1000).
- Point targets at staging/preview or dedicated test endpoints (Filament dashboards, health checks) to avoid disrupting production. Keep durations/concurrency small for shared infra.
- First run downloads the k6 binary; cache it between runs to avoid repeated downloads in CI.

## Example (expectation style)
```php
use function Pest\Stressless\stress;

$result = stress('https://staging.example.com/filament/app')
    ->concurrently(requests: 4)
    ->for(8)
    ->seconds();

expect($result->requests()->failed()->count())->toBe(0)
    ->and($result->requests()->duration()->p95())->toBeLessThan(800.0);
```

## Quick CLI checks
- Baseline GET: `./vendor/bin/pest stress https://staging.example.com/health --duration=5 --concurrency=2`
- POST with payload: `./vendor/bin/pest stress https://staging.example.com/api/webhook --post='{\"ping\":true}' --duration=5`
- Verbose debugging while tuning: append `--verbose` or call `->verbosely()` in the `stress()` chain to inspect response timings and bodies.
