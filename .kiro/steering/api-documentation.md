# API Documentation Integration

Relaticle CRM uses `dedoc/scramble` to automatically generate API documentation for all API resources. This ensures that our documentation is always up-to-date with the codebase.

## Overview
- **Package**: `dedoc/scramble`
- **URL**: `/docs/api`
- **Access**: Restricted to authenticated users (Admins) via `viewApiDocs` gate (default behavior).

## Key Features
- **Automatic Generation**: No need to write manual OpenAPI specs.
- **Reflection**: Uses PHP reflection to understand return types and request validation.
- **Live Updates**: Documentation reflects the code state instantly.

## How to Document API Resources
To ensure the best quality documentation, follow these rules:

### 1. Type Hinting
Always type hint your controller methods and API resources. Scramble relies on return types to generate the response schema.

```php
public function index(): AnonymousResourceCollection
{
    return UserResource::collection(User::paginate());
}

public function show(User $user): UserResource
{
    return new UserResource($user);
}
```

### 2. PHPDocs for Descriptions
Use PHPDocs to add descriptions to endpoints and parameters.

```php
/**
 * List all users.
 *
 * Retrieve a paginated list of users.
 */
public function index() ...
```

### 3. API Resources
Ensure your `JsonResource` classes define the structure clearly in `toArray`.

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        // ...
    ];
}
```

## Filament Integration
The API documentation is accessible via the Filament admin panel. A navigation item "API Utils -> API Documentation" links directly to the docs.

## Automatic Updates
The OpenAPI specification is automatically exported to `api.json` whenever API-related files are modified. This is handled by the `.kiro/hooks/scramble-export.kiro.hook` file, ensuring that the static documentation file is always up-to-date.

## Configuration
Configuration is located in `config/scramble.php`.
- **UI Title**: Custom title for the docs.
- **Theme**: Light/Dark mode settings.
- **Security**: Gate definition in `AppServiceProvider` or via Shield.
