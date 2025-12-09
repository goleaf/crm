# Filament DB Config Integration

## Overview
- DB-backed key/value store for runtime settings and editable content using `inerba/filament-db-config`.
- Stores values in the `db_config` table and caches reads with automatic invalidation on write.
- Includes a Filament page for admins to manage content without touching code or deployments.

## Installation & Setup
1. Package installed via Composer: `composer require inerba/filament-db-config`.
2. Published config and migration:
   - `php artisan vendor:publish --tag="db-config-config"`
   - `php artisan vendor:publish --tag="db-config-migrations"`
3. Run the migration to create the `db_config` table: `php artisan migrate`.
4. Environment switches:
   - `DB_CONFIG_TABLE` (defaults to `db_config`)
   - `DB_CONFIG_CACHE_PREFIX` (defaults to `db-config`)
   - `DB_CONFIG_CACHE_TTL` (minutes; `null`/`0` caches forever)

## Admin Experience
- New Filament page: **Content Settings** (Settings cluster) backed by the `app-content` group.
- Sections managed:
  - **Brand voice**: brand name, tagline, value proposition.
  - **Primary call to action**: CTA toggle, label, URL, helper text.
  - **Support & trust**: support email + URL.
  - **Announcement banner**: toggle + message.
- Page shows last-updated timestamp and saves via the package's helper pipeline.

## Reading & Writing Values
- Read anywhere with the helper (preferred):
  ```php
  $ctaLabel = db_config('app-content.cta.label', 'Start a workspace');
  $announcement = db_config('app-content.announcement.message');
  ```
- Programmatic writes when needed:
  ```php
  \Inerba\DbConfig\DbConfig::set('app-content.announcement.message', 'We will be upgrading tonight at 10pm UTC.');
  ```
- Values are cached using the configured prefix/TTL; `cache:clear` resets everything.

## When to Use vs. Existing SettingsService
- **Use DB Config** for lightweight, admin-editable content and simple runtime toggles that don't require typed DTOs.
- **Use SettingsService** (and the `settings` table) for typed, team-aware configuration that should live alongside domain logic.
- Do not store secrets or credentials in DB Config; keep them in env/config or encrypted settings.

## Permissions
- The page participates in Filament Shield; run `php artisan shield:generate --all` after deploying to refresh page permissions/roles.
