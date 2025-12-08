# Laragear Populate Integration

**Date:** July 16, 2025  
**Package:** `laragear/populate` (`v1.1.1`)

## Overview

Laragear Populate replaces Laravel's base seeder with step-aware seeders that support per-step transactions, skip/continue handling, and clearer console output. The project now uses Populate for all entry-point seeders:
- `Database\Seeders\DatabaseSeeder` exposes a single seed step that calls the consolidated demo data.
- `Database\Seeders\ConsolidatedSeeder` is split into Populate seed steps (users/team, accounts, companies, people, leads, opportunities, tasks, notes, invoices, support cases, knowledge base, processes) with before/after hooks for start/end logging.
- `Relaticle\OnboardSeed\OnboardSeeder` seeds personal-team onboarding data through a Populate seed step and is invoked from `CreateTeamCustomFields`.

## Running seeders

- Default: `php artisan db:seed` (runs Populate steps; each step is wrapped in a transaction).
- Resume failed runs: `php artisan db:seed --continue` (replays only unfinished steps using `storage/framework/seeding` state).
- Run a specific seeder: `php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --continue`.
- Pass step parameters (Populate requirement): supply an array keyed by the seed-step name, e.g. `app(Relaticle\OnboardSeed\OnboardSeeder::class)(['seedOnboardingData' => ['user' => $owner]]);`.

## Authoring new seeders/steps

- Extend `Laragear\Populate\Seeder`; avoid a `run()` method so Populate can discover steps.
- Create **public** methods prefixed with `seed` (or add the `#[SeedStep]` attribute) for each discrete block of data.
- Use `before()`/`after()` for setup/teardown; call `$this->skip('reason')` to bypass a step or the entire seeder when appropriate.
- Keep steps order-dependent when needed (declare methods in execution order) and rely on per-step transactions instead of a single outer transaction.
- When a seed step needs external input, pass an entry keyed by the method name (see OnboardSeeder usage above).

## Filament / onboarding note

Personal-team onboarding now runs through Populate. `CreateTeamCustomFields` invokes `OnboardSeeder` with the owning user via the Populate parameter format so the step participates in continue/skip behavior without bespoke logic.
