# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains controllers, models, console commands, and service providers.
- `app-modules/` stores Relaticle packages (SystemAdmin, Documentation, OnboardSeed) with their own `src`, configs, and migrations via Composer’s PSR-4 autoload.
- `resources/` (JS, CSS, Blade snippets) handle frontend assets built by Vite/Tailwind; `routes/`, `config/`, and `lang/` follow Laravel layout.
- `database/` carries migrations, seeders, and a committed SQLite file for quick local work; `tests/` uses Pest with `Feature`, `Unit`, and `ArchTest.php` suites.

## Build, Test, and Development Commands
- `composer install` installs PHP packages, copies `.env.example`, generates the app key, and touches `database/database.sqlite`.
- `composer dev` starts artisan serve, the queue listener, Pail log tailer, and `npm run dev` via `npx concurrently`.
- `npm run dev` runs the Vite dev server; `npm run build` compiles production assets.
- `composer lint` runs `rector` and `pint --parallel` to keep formatting and API surfaces aligned before commits.
- `composer test` executes linting, Rector dry-run checks, `phpstan analyse`, and the default Pest parallel suite; `composer test:ci` mirrors the CI pipeline with the dedicated phpunit config.

## Coding Style & Naming Conventions
- Follow PSR-12 for PHP classes (`App\`, `Relaticle\*`) and keep Blade components in kebab-case, while Livewire classes stay PascalCase.
- Use `pint` (and the `pint.json` config) to format PHP/Blade files; `rector` keeps typed props and modern Laravel APIs consistent during refactors.
- Frontend JS leverages Vite + Tailwind; keep entrypoints (`resources/js/app.js`) lean and prefer semantic class names documented in `resources/css`.

## Testing Guidelines
- Pest drives the test suite; place specs under `tests/Feature` or `tests/Unit`, suffix files with `*Test.php`, and share helpers in `tests/Pest.php`.
- Coverage gates are enforced by `composer test:type-coverage` (`pest --type-coverage --min=99.9`) and `composer test:coverage` (`pest --coverage --min=80`).
- `composer test:types` (`phpstan analyse`) and `composer test:pest:ci` (`phpunit.ci.xml`) validate static analysis and CI-specific shards when needed.

## Commit & Pull Request Guidelines
- Commit messages follow the conventional `<type>: <summary>` pattern (`feat:`, `fix:`, `chore:`) with a short imperative subject.
- PR descriptions should summarize behavior changes, link relevant issues/RFCs, and list verification steps (e.g., `composer lint`, `composer test:coverage`).
- Generated files (`bootstrap/cache`, `storage`, `vendor`) should remain unchanged unless required for a deployment artifact.

## Environment & Configuration Tips
- Composer scripts already copy `.env.example` and touch the SQLite file, but double-check `.env` values before running `composer dev`.
- Clear cached config when providers or module bindings change via `php artisan optimize:clear`.
