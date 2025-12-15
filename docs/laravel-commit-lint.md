# Laravel Commit Lint Integration

## Overview
- Enforces Conventional Commits using the `mubbi/laravel-commit-lint` package.
- Installs a `commit-msg` hook that blocks non-compliant messages while allowing merge/WIP/revert commits.
- Keeps commit history consistent with the repository’s Git workflow guides.

## Installation & Maintenance
1) Install dependencies (already added to `composer.json` as a dev dependency).
2) Install or refresh the hook locally:  
   ```bash
   php artisan commitlint:install
   ```
3) If hooks are wiped (e.g., after cloning or switching worktrees), rerun the command above to restore enforcement.

## Usage
- Author commits in Conventional Commit format: `type(scope): description` (types: feat, fix, docs, style, refactor, test, chore, build, ci, perf).
- Invalid messages are rejected with guidance; adjust the message and retry the commit.

## Customization
- Custom hook path: `php artisan commitlint:install /custom/path/to/commit-msg`
- Custom hook stub: `php artisan commitlint:install --stub=/path/to/custom-stub`
