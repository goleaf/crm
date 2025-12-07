# Extension Framework

The Extension Framework provides a safe and flexible way to extend the platform with custom logic hooks, controllers, views, and other customizations.

## Overview

The framework includes:
- **Extension Registry**: Central service for managing extensions
- **Logic Hooks**: Event-driven customizations that run at specific points
- **Guardrails**: Security and stability protections including permission checks, recursion limits, and error isolation

## Key Features

### Safety Guardrails
- **Permission Checks**: Extensions respect user permissions
- **Recursion Prevention**: Automatic detection and prevention of infinite loops
- **Error Isolation**: Failed extensions don't crash the system
- **Scoped Context**: Extensions run in isolated contexts with sensitive data removed
- **Execution Limits**: Timeouts and resource limits prevent runaway extensions

### Extension Types
- Logic Hooks
- Entry Points
- Controllers
- Views
- Metadata/Vardefs
- Language Strings
- Schedulers
- Dashlets
- Modules
- Relationships
- Calculations

## Usage

### Registering an Extension

```php
use App\Services\ExtensionRegistry;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;

$registry = app(ExtensionRegistry::class);

$extension = $registry->register(
    teamId: $team->id,
    creatorId: $user->id,
    name: 'My Custom Hook',
    slug: 'my-custom-hook',
    type: ExtensionType::LOGIC_HOOK,
    handlerClass: MyCustomHandler::class,
    targetModel: 'App\\Models\\Company',
    targetEvent: HookEvent::AFTER_SAVE,
    priority: 100
);
```

### Creating a Handler

```php
namespace App\Extensions;

class MyCustomHandler
{
    public function handle(array $context): array
    {
        // Your custom logic here
        // Modify and return the context
        
        return $context;
    }
}
```

### Activating an Extension

```php
$registry->activate($extension);
```

### Executing Hooks

```php
$context = ['model' => $company, 'user_id' => $userId];

$result = $registry->executeHook(
    targetModel: 'App\\Models\\Company',
    event: HookEvent::AFTER_SAVE,
    context: $context
);
```

## Security

### Permission Requirements

Extensions can require specific permissions:

```php
$extension = $registry->register(
    // ... other parameters
    permissions: [
        'required_permissions' => ['manage-extensions', 'edit-companies']
    ]
);
```

### Sensitive Data Protection

The framework automatically removes sensitive fields from context:
- `password`
- `token`
- `secret`

### Critical Field Preservation

Critical fields are always preserved:
- `id`
- `team_id`

## Monitoring

### Extension Statistics

```php
$stats = $registry->getStatistics($extension);

// Returns:
// - total_executions
// - total_failures
// - success_rate
// - last_executed_at
// - avg_execution_time_ms
// - recent_executions
```

### Automatic Disabling

Extensions are automatically disabled after 10 consecutive failures to prevent system degradation.

## Testing

The framework includes comprehensive tests:
- Unit tests for core functionality
- Property-based tests for safety guarantees

See `tests/Unit/Services/ExtensionRegistryTest.php` and `tests/Unit/Services/ExtensionRegistryPropertyTest.php` for examples.
