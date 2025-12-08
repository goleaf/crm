# Documentation Index

## Quick Start Guides

### Code Quality & Refactoring
- **[Rector v2 Quick Start](RECTOR_V2_QUICK_START.md)** - Fast guide to automated refactoring
- **[Rector v2 Integration](rector-v2-integration.md)** - Comprehensive Rector v2 setup and usage
- **[Rector Laravel](rector-laravel.md)** - Laravel-specific refactoring rules
- **[PHPCS Standard](phpcs-standard.md)** - PHP CodeSniffer configuration
- **[PHP Insights](phpinsights.md)** - Code quality analysis

### Testing
- **[Testing Infrastructure](testing-infrastructure.md)** - Complete testing setup
- **[Pest Laravel Expectations](pest-laravel-expectations.md)** - HTTP/model/storage assertions
- **[Pest Stressless](pest-stressless.md)** - Performance and stress testing

### Laravel Core
- **[Laravel Container Services](laravel-container-services.md)** - Service container patterns
- **[Laravel Container Implementation](laravel-container-implementation-guide.md)** - Implementation guide
- **[Container Services Integration](CONTAINER_SERVICES_INTEGRATION.md)** - Integration summary
- **[Laravel Service Container](laravel-service-container-integration.md)** - Service integration
- **[Laravel Date Scopes](laravel-date-scopes.md)** - Date filtering with scopes
- **[Laravel HTTP Client](laravel-http-client.md)** - HTTP client configuration
- **[Laravel Pail](laravel-pail.md)** - Real-time log tailing
- **[Laravel Precognition](laravel-precognition.md)** - Real-time form validation
- **[Laravel Unique](laravel-unique.md)** - Unique validation rules
- **[Laravel Introspect](laravel-introspect.md)** - Code introspection

## Filament v4.3+

### Resources & UI
- **[Filament Resources](filament-resources.md)** - Resource patterns
- **[Filament Slug Generation](filament-slug-generation.md)** - Automatic slug generation
- **[Filament Underrated Features](filament-underrated-features.md)** - Hidden gems
- **[Empty States Integration](empty-states-integration.md)** - Empty state patterns

### Data & Visualization
- **[Apex Charts Integration](apex-charts-integration.md)** - Chart integration
- **[Easy Metrics Integration](easy-metrics-integration.md)** - Metrics and KPIs

## Packages & Integrations

### Data Management
- **[Array Helpers](array-helpers.md)** - Array/JSON formatting utilities
- **[String Word Wrap](string-word-wrap.md)** - Text wrapping for tables
- **[Taxonomy Integration](taxonomy-integration.md)** - Category/tag system
- **[Referenceable](referenceable.md)** - Reference tracking
- **[Notable Package](notable-package-integration.md)** - Lightweight notes
- **[Model Notes Integration](model-notes-integration.md)** - Rich notes system
- **[Notes Quick Reference](notes-quick-reference.md)** - Notes API reference
- **[Has Notes Trait Analysis](has-notes-trait-analysis.md)** - Notes trait details

### Enums
- **[Enums](enums.md)** - Enum patterns and conventions
- **[Enum Integration Summary](enum-integration-summary.md)** - Integration guide
- **[Enum Quick Reference](enum-quick-reference.md)** - Quick API reference
- **[Enum Usage Examples](enum-usage-examples.md)** - Practical examples

### User Management
- **[Userstamps](userstamps.md)** - Creator/editor tracking
- **[Name of Person](name-of-person.md)** - Name formatting

### Validation & Security
- **[Intervention Validation](intervention-validation.md)** - Image validation
- **[Password Strength](password-strength.md)** - Password validation
- **[Security Headers](security-headers.md)** - HTTP security headers
- **[Squeaky Profanity Filter](squeaky-profanity-filter.md)** - Content filtering

### Utilities
- **[Cache Eviction](cache-eviction.md)** - Cache management
- **[Geo Genius](geo-genius.md)** - Geolocation utilities
- **[Larapath Integration](larapath-integration.md)** - Path utilities
- **[Reactions](reactions.md)** - Like/reaction system
- **[Toast Magic Integration](toastmagic-integration.md)** - Toast notifications
- **[Zap Integration](zap-integration.md)** - Quick actions

### System Features
- **[Extension Framework](extension-framework.md)** - Plugin system
- **[Feature Flags](feature-flags.md)** - Feature toggles
- **[System Settings](system-settings.md)** - Application settings
- **[System Settings Quick Reference](system-settings-quick-reference.md)** - Settings API
- **[Settings Usage Guide](settings-usage-guide.md)** - Settings patterns

## Internationalization

- **[Internationalization](internationalization.md)** - i18n setup
- **[i18n Implementation Summary](i18n-implementation-summary.md)** - Implementation guide
- **[Translation Example](translation-example.md)** - Translation patterns

## Performance Optimization

- **[Performance Calendar Events](performance-calendar-events.md)** - Calendar optimization
- **[Performance Calendar Events Implementation](performance-calendar-events-implementation-notes.md)** - Implementation notes
- **[Performance Calendar Page](performance-calendar-page.md)** - Calendar page optimization
- **[Performance Lead Seeder](performance-lead-seeder.md)** - Seeder optimization
- **[Performance Project Schedule](performance-project-schedule.md)** - Schedule optimization
- **[Performance Settings Optimization](performance-settings-optimization.md)** - Settings optimization
- **[Performance ViewCompany](performance-viewcompany.md)** - Company view optimization

## UI/UX

- **[Tailwind 3.4 Integration](tailwind-3-4-integration.md)** - Tailwind utilities
- **[UI/UX Documentation](ui-ux/)** - UI/UX patterns and guidelines

## API Documentation

- **[API Documentation](api/)** - API reference and examples

## Database

- **[Seeders Documentation](seeders/)** - Database seeding patterns
- **[Calendar Event Meeting Fields](calendar-event-meeting-fields.md)** - Calendar schema

## Migrations & Changes

- **[Changes](changes.md)** - Changelog and breaking changes
- **[Migration 2026-01-11 Calendar Events](MIGRATION_2026_01_11_CALENDAR_EVENTS.md)** - Calendar migration
- **[Documentation Update Summary](DOCUMENTATION_UPDATE_SUMMARY.md)** - Documentation changes

## Feature Comparisons

- **[SuiteCRM Features](suitecrm-features.md)** - Feature comparison with SuiteCRM
- **[Knowledge Template Responses Filters Widgets](knowledge-template-responses-filters-widgets.md)** - Knowledge base features

## Video Resources

- **[WireLive Playlist](wirelive-playlist.md)** - Video tutorials and demos

## Documentation Standards

### Writing Guidelines
- Use clear, concise language
- Include code examples for all features
- Provide both quick start and comprehensive guides
- Link related documentation
- Keep examples up-to-date with Laravel 12 and Filament v4.3+

### Code Examples
- Use PHP 8.4 syntax
- Follow PSR-12 standards
- Include type declarations
- Show both "before" and "after" for refactoring examples
- Test all code examples

### Organization
- Quick start guides for common tasks
- Comprehensive guides for complex features
- API references for packages
- Performance guides for optimization
- Migration guides for breaking changes

## Contributing to Documentation

1. **Create new documentation** in `docs/` directory
2. **Update this index** with new entries
3. **Follow naming conventions**: kebab-case for filenames
4. **Include frontmatter** if using special formatting
5. **Cross-reference** related documentation
6. **Test code examples** before committing
7. **Run Rector** on code examples: `composer lint`

## Getting Help

- Check relevant documentation first
- Review `.kiro/steering/` for coding standards
- See `AGENTS.md` for repository guidelines
- Run `composer test` to verify changes
- Use `composer lint` before committing

## Quick Command Reference

```bash
# Code Quality
composer lint                    # Run Rector + Pint
composer test:refactor          # Check pending refactors
composer test:types             # Run PHPStan

# Testing
composer test                   # Full test suite
composer test:pest              # Run Pest tests
composer test:coverage          # Coverage report

# Development
composer dev                    # Start dev environment
composer install                # Install dependencies
php artisan optimize:clear      # Clear caches
```

## Documentation Maintenance

This index is automatically updated when new documentation is added. Last updated: 2025-01-11.
