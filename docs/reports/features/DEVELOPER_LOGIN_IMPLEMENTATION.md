# Developer Login Implementation

## Overview
Implemented a "Developer Login" feature that allows quick authentication in local and testing environments without entering credentials.

## Components Created

### 1. Controller
**File**: `app/Http/Controllers/Auth/DeveloperLoginController.php`
- Handles developer login requests
- Only available in `local` and `testing` environments
- Validates email parameter
- Logs authentication attempts
- Redirects to specified URL after login

### 2. Blade Component
**File**: `resources/views/components/login-link.blade.php`
- Renders a styled button with gradient background
- Displays developer login action text
- Shows helpful hint message
- Includes SVG icon for visual appeal

### 3. Routes
**File**: `routes/web.php`

**URL-Based Login:**
- Route: `GET /dev-login`
- Name: `dev.login`
- Parameters: `email` (required), `redirect` (optional, defaults to `/`)
- Only registered in `local` and `testing` environments

**Form-Based Login:**
- Route: `GET /dev-login-form`
- Name: `dev.login.form`
- Class: `App\Filament\Pages\Auth\DeveloperLogin`
- Only registered in `local` and `testing` environments

**Filament Panel Route:**
- Route: `GET /app/dev-login-form` (within Filament panel domain)
- Name: `filament.app.filament.app.dev-login-form`
- Registered via `AppPanelProvider->routes()` method

### 4. Translations
**Files**: `lang/en/app.php`, `lang/uk/app.php`

#### English
- `app.actions.developer_login` → "Developer Login"
- `app.messages.developer_login_hint` → "Quick login for local development"
- `app.messages.developer_login_email_required` → "Email parameter is required for developer login"
- `app.messages.developer_login_user_not_found` → "User with email :email not found"
- `app.messages.developer_login_success` → "Welcome back, :name!"

#### Ukrainian
- `app.actions.developer_login` → "Вхід розробника"
- `app.messages.developer_login_hint` → "Швидкий вхід для локальної розробки"
- `app.messages.developer_login_email_required` → "Параметр email обов'язковий для входу розробника"
- `app.messages.developer_login_user_not_found` → "Користувача з email :email не знайдено"
- `app.messages.developer_login_success` → "З поверненням, :name!"

## Integration

### Filament Panel Provider
**File**: `app/Providers/Filament/AppPanelProvider.php`

The component is rendered on the login page via a render hook:
```php
->renderHook(
    PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
    fn (): string => Blade::render('@env(\'local\')<x-login-link email="manuk.minasyan1@gmail.com" redirect-url="'.url('/').'" />@endenv'),
)
```

## Usage

### In Browser
1. Navigate to the login page: `https://app.crm.test/login`
2. Click the "Developer Login" button (generates signed URL automatically)
3. Automatically logged in as the specified user
4. Redirected to the specified URL (default: `/`)

### Programmatic URL Generation

```php
// Direct URL (works in local/testing environments)
$url = route('dev.login', ['email' => 'user@example.com']);

// With redirect
$url = route('dev.login', [
    'email' => 'user@example.com',
    'redirect' => '/dashboard',
]);
```

**Note:** The route only exists in local/testing environments. In production, it returns 404.

## Security

- **Environment Restriction**: Only available in `local` and `testing` environments
- **404 Response**: Returns 404 in production environment
- **Logging**: All developer login attempts are logged with user ID, email, and IP address
- **No Password Required**: Bypasses password authentication (intentional for development)

### Security Model

**Custom dev.login Route:**
- Route only registered in local/testing environments
- Controller returns 404 in production as fallback
- All login attempts logged with IP address

**Spatie Login-Link Package:**
- Environment restricted to `local` only
- Host restrictions prevent unauthorized domains
- CSRF protection via POST forms

See `docs/auth/developer-login.md` for complete details.

## Testing

### Test Files
1. **`tests/Feature/Auth/DeveloperLoginTest.php`** (13 tests)
   - Valid email login
   - Custom redirect URL
   - Missing email parameter
   - Non-existent user
   - Production environment restriction
   - Logging verification
   - Empty/whitespace email handling
   - Case-sensitive email matching
   - Special characters in redirect URL
   - Default redirect path
   - Session data verification
   - Various email formats

2. **`tests/Feature/Components/LoginLinkComponentTest.php`** (4 tests)
   - Correct route rendering
   - Default redirect URL
   - SVG icon presence
   - CSS classes application

### Running Tests
```bash
# Run all developer login tests
vendor/bin/pest tests/Feature/Auth/DeveloperLoginTest.php --no-coverage

# Run component tests
vendor/bin/pest tests/Feature/Components/LoginLinkComponentTest.php --no-coverage

# Run all tests
composer test
```

## Benefits

1. **Faster Development**: No need to remember or type passwords during development
2. **Easy User Switching**: Quickly switch between different user accounts for testing
3. **Secure**: Only available in development environments
4. **Logged**: All attempts are logged for audit purposes
5. **Flexible**: Supports custom redirect URLs
6. **Localized**: Fully translated in English and Ukrainian

## Future Enhancements

Potential improvements:
- Add support for multiple pre-configured users
- Add dropdown to select from available users
- Add role-based quick login (admin, manager, user)
- Add session duration configuration
- Add IP whitelist for additional security

## Related Files

- Controller: `app/Http/Controllers/Auth/DeveloperLoginController.php`
- Component: `resources/views/components/login-link.blade.php`
- Routes: `routes/web.php`
- Translations: `lang/en/app.php`, `lang/uk/app.php`
- Tests: `tests/Feature/Auth/DeveloperLoginTest.php`, `tests/Feature/Components/LoginLinkComponentTest.php`
- Panel Provider: `app/Providers/Filament/AppPanelProvider.php`
