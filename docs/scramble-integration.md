# Scramble API Documentation Integration

This document outlines the integration of `dedoc/scramble` for automated API documentation in Relaticle CRM.

## Overview
`dedoc/scramble` automatically generates OpenAPI documentation for your Laravel API without requiring manual annotations for every field. It uses PHP reflection and code analysis.

- **URL**: `/docs/api`
- **Config**: `config/scramble.php`
- **Gate**: `viewApiDocs`

## Integration Details

### Authentication & Security
Access to the documentation is restricted to users who pass the `viewApiDocs` gate. This is typically configured in `App\Providers\AppServiceProvider`.

```php
Gate::define('viewApiDocs', function (User $user) {
    return $user->hasRole('super_admin') || $user->can('view_api_docs');
});
```

### Extending Documentation
You can extend the generated documentation using:
- **PHPDocs**: Add descriptions to methods and classes.
- **Type Hints**: Ensure controller methods return `JsonResource` or other typed responses.
- **Scramble Extensions**: Custom extensions for specific libraries if needed.

### Filament Integration
The documentation is integrated into Filament via a navigation item that opens the docs in a new tab.

## Troubleshooting
- **Missing Routes**: Ensure routes are in `routes/api.php` or configured path.
- **Missing Response Data**: Ensure methods have return types (e.g., `: UserResource`).
- **Generation Errors**: Check `laravel.log` for reflection errors. Scramble may fail if code has syntax errors or invalid types.

## maintenance
- Update the package via `composer update dedoc/scramble`.
- Review `config/scramble.php` for new options.
