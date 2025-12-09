# Git Workflow and Branching Strategy

## Branch Naming Convention
- Feature branches: `feature/description-of-feature`
- Bug fixes: `fix/description-of-bug`
- Hotfixes: `hotfix/critical-issue-description`
- Releases: `release/version-number`

## Commit Message Format
Follow conventional commits format:
```
type(scope): description

[optional body]

[optional footer]
```

Types: feat, fix, docs, style, refactor, test, chore

- Enforcement: a `commit-msg` hook from `mubbi/laravel-commit-lint` blocks non-Conventional Commit messages (merge/WIP/revert commits are skipped). Reinstall after cloning or when hooks are reset with `php artisan commitlint:install`.

## Pull Request Guidelines
- Create PR from feature branch to main/develop
- Include clear description of changes
- Link related issues using keywords (fixes #123)
- Ensure all tests pass before requesting review
- Squash commits when merging to keep history clean

## Code Review Process
- At least one approval required before merge
- Review for code quality, security, and performance
- Check that tests cover new functionality
- Verify documentation is updated if needed
- Ensure no breaking changes without proper versioning
