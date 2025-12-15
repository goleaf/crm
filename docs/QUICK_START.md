# Quick Start Guide

> **New to the project?** Start here for the fastest path to productivity.

## 🚀 First Steps

### 1. Setup (5 minutes)
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run development server
composer dev
```

### 2. Read Core Documentation (30 minutes)
**Must Read**:
1. [`AGENTS.md`](../AGENTS.md) - Repository guidelines
2. [`docs/README.md`](README.md) - Documentation index
3. [`.kiro/steering/laravel-conventions.md`](../.kiro/steering/laravel-conventions.md) - Coding standards

### 3. Explore the Codebase (30 minutes)
- Browse `app/` - Application code
- Check `app-modules/` - Custom packages
- Review `tests/` - Test examples
- Look at `docs/` - Comprehensive guides

## 📚 Essential Documentation

### When You Need To...

#### **Validate Forms**
→ [`docs/laravel-validation-enhancements.md`](laravel-validation-enhancements.md)
- Modern validation patterns
- Form Requests
- Custom rules
- Precognition (real-time validation)

#### **Create Controllers**
→ [`docs/controller-refactoring-guide.md`](controller-refactoring-guide.md)
- Thin controllers
- Action classes
- Single Action Controllers
- Service integration

#### **Write Tests**
→ [`docs/testing-infrastructure.md`](testing-infrastructure.md)
- Pest patterns
- Feature tests
- Unit tests
- Mocking strategies

#### **Optimize Slow Tests**
→ [`docs/test-profiling.md`](test-profiling.md)
```bash
composer test:pest:profile
```

#### **Use Services**
→ [`docs/laravel-container-services.md`](laravel-container-services.md)
- Dependency injection
- Service registration
- Singleton vs bind

#### **Work with Filament**
→ [`.kiro/steering/filament-conventions.md`](../.kiro/steering/filament-conventions.md)
- Resources
- Forms
- Tables
- Actions

#### **Manage Translations**
→ [`docs/localazy-github-actions-integration.md`](localazy-github-actions-integration.md)
```bash
php artisan translations:export
php artisan translations:import
```

#### **Create Shareable Links**
→ [`docs/laravel-sharelink-integration.md`](laravel-sharelink-integration.md)
```php
$link = $shareLink->createTemporaryLink($model, hours: 24);
```

## 🎯 Common Tasks

### Run Tests
```bash
# All tests
composer test

# With profiling
composer test:pest:profile

# With coverage
composer test:coverage

# Specific suite
php artisan test --testsuite=Feature
```

### Code Quality
```bash
# Lint (Rector + Pint)
composer lint

# Type checking
composer test:types

# Static analysis
composer test:refactor
```

### Development
```bash
# Start dev server (includes queue, pail, vite)
composer dev

# Just Laravel
php artisan serve

# Just Vite
npm run dev
```

### Database
```bash
# Migrate
php artisan migrate

# Seed
php artisan db:seed

# Fresh
php artisan migrate:fresh --seed
```

## 🔍 Finding Information

### "How do I...?"
1. Check [`docs/README.md`](README.md) index
2. Find relevant comprehensive guide
3. Read the guide
4. Check steering rules for conventions
5. Look at existing code for examples

### "Where is...?"
- **Controllers**: `app/Http/Controllers/`
- **Actions**: `app/Actions/`
- **Services**: `app/Services/`
- **Models**: `app/Models/`
- **Tests**: `tests/Feature/`, `tests/Unit/`
- **Filament**: `app/Filament/`
- **Docs**: `docs/`
- **Steering**: `.kiro/steering/`

### "What pattern should I use?"
1. Check [`docs/README.md`](README.md)
2. Find the pattern category
3. Read the comprehensive guide
4. Follow the documented pattern

## ⚡ Quick Commands Reference

### Testing
```bash
composer test                    # Full test suite
composer test:pest              # Pest tests (parallel)
composer test:pest:profile      # Profile slow tests
composer test:coverage          # With code coverage
composer test:types             # PHPStan
composer test:routes            # Route tests
composer test:translations      # Translation check
```

### Code Quality
```bash
composer lint                   # Rector + Pint
composer test:refactor          # Rector dry-run
pint                           # Format only
```

### Development
```bash
composer dev                    # Full dev stack
php artisan serve              # Laravel only
npm run dev                    # Vite only
php artisan pail               # Log tailing
```

### Filament
```bash
php artisan shield:generate --all    # Generate permissions
php artisan filament:user           # Create admin user
```

### Translations
```bash
php artisan translations:import     # Import from files
php artisan translations:export     # Export to files
php artisan translations:sync       # Sync database
```

## 🎨 Code Style

### PHP
- PSR-12 standard
- Type hints everywhere
- Readonly properties
- Constructor injection
- No service locator pattern

### Example
```php
<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Notifications\NotificationService;

class ApproveOrder
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}
    
    public function execute(Order $order, array $data): Order
    {
        // Business logic here
        return $order;
    }
}
```

### Filament
```php
use Filament\Forms\Components\TextInput;

TextInput::make('email')
    ->label(__('app.labels.email'))
    ->email()
    ->required()
    ->precognitive()
    ->live(onBlur: true);
```

## 🚨 Common Mistakes to Avoid

### ❌ DON'T
- Put business logic in controllers
- Skip Form Requests for validation
- Use service locator (`app()`, `resolve()`) in business logic
- Hardcode strings (use translations)
- Skip tests
- Ignore profiling results
- Guess patterns (check docs first)

### ✅ DO
- Extract logic to Action classes
- Use Form Requests for validation
- Inject dependencies via constructor
- Use translation keys
- Write tests for everything
- Profile and optimize slow tests
- Follow documented patterns

## 📖 Learning Path

### Week 1: Basics
1. Setup development environment
2. Read `AGENTS.md` and `docs/README.md`
3. Explore codebase structure
4. Run tests and understand test suite
5. Make small changes to existing features

### Week 2: Patterns
1. Read validation guide
2. Read controller refactoring guide
3. Read service container guide
4. Implement a simple feature using patterns
5. Write tests for your feature

### Week 3: Advanced
1. Read Filament conventions
2. Read testing infrastructure
3. Profile and optimize tests
4. Implement a complex feature
5. Review code with team

### Week 4: Mastery
1. Read all integration guides
2. Contribute to documentation
3. Help onboard new developers
4. Propose improvements
5. Share knowledge with team

## 🆘 Getting Help

### Documentation
1. [`docs/README.md`](README.md) - Complete index
2. [`AGENTS.md`](../AGENTS.md) - Repository guidelines
3. [`.kiro/steering/`](../.kiro/steering/) - Conventions

### Code Examples
- Look at existing code in `app/`
- Check tests in `tests/`
- Review Filament resources in `app/Filament/`

### Team
- Ask in team chat
- Request code review
- Pair programming sessions

## 🎯 Success Checklist

Before submitting a PR:
- [ ] Code follows documented patterns
- [ ] Tests written and passing
- [ ] Code coverage maintained
- [ ] Translations added for UI text
- [ ] Documentation updated if needed
- [ ] `composer lint` run
- [ ] `composer test` passing
- [ ] No slow tests added (profile if needed)

## 🔗 Important Links

- **Documentation Index**: [`docs/README.md`](README.md)
- **Repository Guidelines**: [`AGENTS.md`](../AGENTS.md)
- **Integration Summary**: [`INTEGRATION_ENHANCEMENTS_COMPLETE.md`](../INTEGRATION_ENHANCEMENTS_COMPLETE.md)
- **Steering Rules**: [`.kiro/steering/`](../.kiro/steering/)

---

**Welcome to the team! 🎉**

Start with the documentation, follow the patterns, write tests, and ask questions. You'll be productive in no time!
