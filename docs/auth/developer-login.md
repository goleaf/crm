# Developer Login Feature

## Overview

The Developer Login feature provides password-less authentication for local development and testing environments. This allows developers to quickly switch between user accounts without managing passwords.

**Version**: Laravel 12.x + Filament 4.3+  
**Status**: Development/Testing Only  
**Security Level**: Local/Testing Environments Only

## Architecture

### Components

- **Controller**: `App\Http\Controllers\Auth\DeveloperLoginController`
- **Route**: `dev.login` (GET `/dev-login`)
- **Tests**: `tests/Feature/Auth/DeveloperLoginTest.php`
- **Login Page Integration**: `App\Filament\Pages\Auth\Login`

### Flow Diagram

```
User Request → Route Guard (env check) → Controller
                                              ↓
                                    Email Validation
                                              ↓
                                    User Lookup
                                              ↓
                                    Auth::login()
                                              ↓
                                    Log Activity
                                              ↓
                                    Redirect + Flash Message
```

## Usage

### Form-Based Login (Recommended)

Access the developer login form at `/dev-login-form`:

```
GET /dev-login-form
```

This provides a user-friendly dropdown interface for selecting users to log in as.

**Routes Available:**
- **Web Route**: `GET /dev-login-form` → `dev.login.form` (registered in `routes/web.php`)
- **Filament Route**: `GET /app/dev-login-form` → `filament.app.filament.app.dev-login-form` (registered in panel provider)

### Direct URL Access

Access the developer login directly via URL:

```
GET /dev-login?email=user@example.com
GET /dev-login?email=user@example.com&redirect=/dashboard
```

### Login Page Integration

When in local/testing environment, the Filament login page displays a "Developer Login" link that automatically logs in as the first user in the database.

### Programmatic Usage

```php
// Generate developer login URL
$url = route('dev.login', [
    'email' => 'user@example.com',
    'redirect' => '/dashboard',
]);
```

## API Reference

### Route Definition

```php
// routes/web.php
if (app()->environment(['local', 'testing'])) {
    Route::get('/dev-login', DeveloperLoginController::class)
        ->name('dev.login');
}
```

### Controller Method

```php
public function __invoke(Request $request): RedirectResponse
```

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `email` | string | Yes | - | User email address |
| `redirect` | string | No | Tenant dashboard | Post-login redirect URL |

#### Responses

| Status | Condition | Redirect | Flash Message |
|--------|-----------|----------|---------------|
| 302 | Success | `$redirect` or tenant dashboard | `success` with user name |
| 302 | Missing email | `/login` | `error` - email required |
| 302 | User not found | `/login` | `error` - user not found |
| 404 | Production env | - | - |

### Multi-Tenancy Support

When no explicit redirect URL is provided, the controller automatically redirects to the user's current team dashboard in the Filament panel. This ensures proper tenant context is maintained after login.

```php
// Redirect behavior:
// 1. If explicit redirect URL provided → use it
// 2. If user has a current team → redirect to tenant dashboard
// 3. Otherwise → redirect to root URL (/)
```

## Security

### Environment Restrictions

The feature is **only available** when:

```php
app()->environment(['local', 'testing'])
```

In production:
- The route is not registered
- The controller returns a 404 response as a fallback

### Logging

All authentication attempts are logged:

```php
Log::info('Developer login', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => $request->ip(),
]);
```

### Best Practices

✅ **DO**:
- Use only in local/testing environments
- Log all authentication attempts
- Validate email parameter exists
- Check user exists before authentication
- Use translation keys for messages

❌ **DON'T**:
- Enable in production
- Skip environment checks
- Bypass user validation
- Hardcode error messages
- Log sensitive data

## Testing

### Test Coverage

The feature has comprehensive test coverage in `tests/Feature/Auth/DeveloperLoginTest.php`:

```php
// Test cases
✓ allows developer login with valid email in local environment
✓ redirects to specified URL after developer login
✓ returns error when email is not provided
✓ returns error when user does not exist
✓ is not available in production environment
✓ logs developer login activity
✓ handles empty email parameter
✓ handles whitespace-only email parameter
✓ is case-sensitive for email matching
✓ handles special characters in redirect URL
✓ redirects to tenant dashboard when redirect is empty
✓ authenticates user with correct session data
✓ works with users having different email formats
✓ redirects to tenant dashboard when user has a team
✓ falls back to root when user has no team
```

### Running Tests

```bash
# Run all auth tests
pest tests/Feature/Auth

# Run developer login tests only
pest tests/Feature/Auth/DeveloperLoginTest.php

# Run with coverage
pest tests/Feature/Auth/DeveloperLoginTest.php --coverage
```

### Test Examples

#### Successful Login

```php
it('allows developer login with valid email', function (): void {
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    $response = $this->get(route('dev.login', ['email' => $user->email]));
    
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);
    $response->assertRedirect('/');
});
```

#### Custom Redirect

```php
it('redirects to specified URL', function (): void {
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    $response = $this->get(route('dev.login', [
        'email' => $user->email,
        'redirect' => '/dashboard',
    ]));
    
    expect(Auth::check())->toBeTrue();
    $response->assertRedirect('/dashboard');
});
```

#### Error Handling

```php
it('returns error when user does not exist', function (): void {
    $response = $this->get(route('dev.login', [
        'email' => 'nonexistent@example.com'
    ]));
    
    expect(Auth::check())->toBeFalse();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});
```

## Translation Keys

### Required Translations

In `lang/en/app.php`:

```php
'actions' => [
    'developer_login' => 'Developer Login',
],
'messages' => [
    'developer_login_email_required' => 'Email is required for developer login.',
    'developer_login_user_not_found' => 'User with email :email not found.',
    'developer_login_success' => 'Logged in as :name',
    'developer_login_hint' => 'Local environment only - Quick login for development',
],
```

## Integration

### Filament Login Page

The login page automatically shows a "Developer Login" link in local/testing environments:

```php
// In App\Filament\Pages\Auth\Login
public function getSubheading(): string|Htmlable|null
{
    if (! app()->environment(['local', 'testing'])) {
        return parent::getSubheading();
    }

    $user = User::first();
    // Shows link to login as first user
}
```

### Testing Integration

Use in Pest tests for quick authentication:

```php
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->get(route('dev.login', ['email' => $this->user->email]));
});

it('can access protected route', function () {
    $this->get('/dashboard')->assertOk();
});
```

## Troubleshooting

### Route Not Found

**Problem**: 404 error when accessing `/dev-login`

**Solution**: Ensure `APP_ENV=local` or `APP_ENV=testing` in `.env`

### User Not Found Error

**Problem**: "User not found" message despite valid email

**Solution**: 
- Check database has user with that email
- Verify email is exact match (case-sensitive)
- Run `php artisan db:seed` if using seeders

### Not Redirecting

**Problem**: Login succeeds but doesn't redirect

**Solution**:
- Check `redirect` parameter is valid URL
- Verify route exists: `php artisan route:list`
- Clear route cache: `php artisan route:clear`

## Related Documentation

- [Spatie Laravel Login Link](https://github.com/spatie/laravel-login-link) - Alternative package for login links
- [Laravel Authentication](https://laravel.com/docs/authentication)
- [Filament Authentication](https://filamentphp.com/docs/panels/users)

## Changelog

### 2025-12-08 (Update 2)
- Added multi-tenancy support for redirect URLs
- When no redirect URL is specified, redirects to user's current team dashboard
- Falls back to root URL if user has no team
- Updated tests to verify tenant-aware redirect behavior

### 2025-12-08
- Simplified implementation (removed signed URL requirement)
- Added login page integration with automatic first-user link
- Updated test suite
- Removed complex form-based login page

### Previous
- Created `DeveloperLoginController`
- Added `dev.login` route
- Added translation keys
