# Password Strength Enforcement

**Package:** `ziming/laravel-zxcvbn`  
**Score Scale:** 0 (weak) – 4 (very strong), default minimum: 3

## Configuration

- Config file: `config/zxcvbn.php` with `ZXCVBN_MIN_SCORE` (default `3`). Raise it to `4` for stricter enforcement.
- User-facing flows reuse the config value; no per-form tuning needed.

## Validation Integration

- `App\Actions\Fortify\PasswordValidationRules` now adds `ZxcvbnRule`, seeding it with available user inputs (name/email) to penalize guessable phrases. The method accepts an optional `$requiresConfirmation` flag for contexts that handle confirmation manually.
- Applied in:
  - Fortify password reset/update actions (`app/Actions/Fortify/ResetUserPassword.php`, `app/Actions/Fortify/UpdateUserPassword.php`).
  - Filament register page (`app/Filament/Pages/Auth/Register.php`) with confirmation handled via `same('passwordConfirmation')`.
  - Profile password update component (`app/Livewire/App/Profile/UpdatePassword.php`).
- Reuse the trait for any new password form:

```php
$rules = $this->passwordRules(
    $user,
    ['email' => $request->input('email'), 'name' => $request->input('name')],
    requiresConfirmation: true,
);
```

## Testing

- `tests/Feature/Profile/UpdatePasswordTest.php` covers successful updates with strong passwords and rejects passwords that miss the configured Zxcvbn score.
