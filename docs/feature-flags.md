# Feature Flags (Laravel Pennant + Filament)

## Overview
- Installs `stephenjude/filament-feature-flags` on top of Laravel Pennant to manage class-based feature flags from Filament.
- Default scope is the current team (`App\Models\Team`); flags resolve to the active Filament tenant or the signed-in user's current team.
- Default state is **off** (`default => false`); enable explicitly per team or for everyone.
- Admin UI lives under `System Settings` → `Feature Flags` and is restricted to team admins with verified emails.

## Admin UI
- Create feature segments (activate/deactivate) against the current team only; the selector is auto-scoped to the active tenant.
- Header actions:
  - **Activate for All Teams**: Enables a feature globally across scopes.
  - **Deactivate for All Teams**: Disables a feature globally.
  - **Purge Feature Flags**: Clears cached Pennant states (single feature or all).
- Tables display friendly feature titles and target team names; records are filtered to the current tenant to avoid cross-tenant visibility.

## Configuration
- `config/filament-feature-flags.php`
  - `default` set to `false`.
  - `scope` set to `Team::class`.
  - `segments` limited to the team `id` with display labels from team names.
  - Resource binding points to `App\Filament\Resources\FeatureFlagResource`.
- `AppServiceProvider::configureFeatureFlags()` wires Pennant's scope resolver to the current Filament tenant (or the user's current team).
- Migrations: `features` (Pennant store) and `feature_segments` (segment rules). Run `php artisan migrate` after deployment.

## Creating & Checking Features
1. Generate a class-based feature:
   ```bash
   php artisan pennant:feature NewDashboard
   ```
2. Add `use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;` to the feature class (inherits default value + segment resolution).
3. Check a feature in code (tenant-aware by default):
   ```php
   use App\FeatureFlags\NewDashboard;
   use Laravel\Pennant\Feature;

   if (Feature::active(NewDashboard::class)) {
       // gated logic
   }

   // Explicit scope if needed
   Feature::for($team)->active(NewDashboard::class);
   ```

## Tenancy & Permissions
- Only team admins can see or manage Feature Flags; navigation hides automatically when no tenant is selected or the user lacks the role.
- Segments and table rows are filtered by `team_id` so one tenant cannot view or edit another tenant's rules.

## Current Flags
- `App\FeatureFlags\NewCalendarExperience` — optimized calendar views, recurrence, performance tweaks.
- `App\FeatureFlags\KnowledgeEnhancements` — enriched knowledge widgets and template response workflows.

## Maintenance Tips
- Use **Purge Feature Flags** after bulk updates or deployments to flush stale Pennant cache.
- Keep feature classes in `app/FeatureFlags`; the package auto-discovers them.
- Add translation keys under `lang/*/app.php` (`feature_flags`, `feature_flag_segment`, etc.) when introducing new UI strings.
