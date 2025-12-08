# Relaticle PHPCS Standard

## What was added
- Local shared coding standard lives at `tools/phpcs-standard/Relaticle/ruleset.xml` (type `phpcodesniffer-standard`).
- Root `phpcs.xml` applies the standard to `app`, `app-modules`, `bootstrap`, `config`, `database`, `lang`, `routes`, and `tests` while skipping vendor/node_modules/coverage/blade files.
- Composer dev deps now include `squizlabs/php_codesniffer`, `dealerdirect/phpcodesniffer-composer-installer`, and the path package `relaticle/phpcs-standard`.
- Composer scripts:
  - `composer phpcs:init` – sets `installed_paths` and default standard to `Relaticle`.
  - `composer lint:phpcs` – runs PHPCS with the project rules.
  - `composer phpcs:fix` – runs `phpcbf` (auto-fix where supported).

## Standard rules (Relaticle)
- Based on `PSR12` with grouped trait imports allowed.
- Line length warning at 160 chars, hard cap 200, comments ignored.
- Suppresses noisy trailing whitespace warnings from Blade exports.

## Usage
1) After `composer install`, run `composer phpcs:init` once to register the standard (configures `installed_paths` and default standard).
2) Lint the codebase: `composer lint:phpcs` (respects `phpcs.xml` scopes, includes Filament v4.3+ resources under `app/Filament`).
3) Attempt fixes: `composer phpcs:fix` (will only fix rules with auto-fixers).
4) Smoke check used here: `vendor/bin/phpcs app/Models/User.php` (passes with Relaticle standard).

## Notes
- The custom standard is available globally as `Relaticle` (`vendor/bin/phpcs -i` lists it).
- Keep phpcs.xml in sync with new PHP paths if more modules or panels are added.
- PHPCS isn’t wired into `composer lint/test` yet; run the dedicated scripts before commits when you want rule coverage.
