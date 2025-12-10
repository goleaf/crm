# Login Link Middleware Configuration

**Date:** 2025-12-08 (Updated)  
**Change Type:** Configuration Correction  
**Risk Level:** 🟢 **LOW**  
**Breaking Change:** ❌ **NO**

---

## ⚠️ SUPERSEDED NOTICE

**This document has been updated.** The original plan to add `signed` middleware was **incorrect** and has been reverted.

---

## Summary

The `config/login-link.php` middleware configuration uses `['web']` only. The `signed` middleware was incorrectly proposed because:

1. **The Spatie package uses POST forms**, not GET requests with signed URLs
2. Adding `signed` middleware would have broken the package's functionality
3. Security is already provided by environment and host restrictions

---

## Current Configuration

**File:** `config/login-link.php`

```php
'middleware' => ['web'],
```

---

## Security Model

### Spatie Login-Link Package
The `spatie/laravel-login-link` package provides security through:

1. **Environment Restrictions**
   ```php
   'allowed_environments' => ['local'],
   ```
   - Only works in `local` environment
   - Throws exception in production/staging

2. **Host Restrictions**
   ```php
   'allowed_hosts' => ['localhost', '127.0.0.1', 'crm.test', 'app.crm.test'],
   ```
   - Only works on configured hosts
   - Prevents access from unauthorized domains

3. **CSRF Protection**
   - Uses POST form submissions
   - Laravel's `web` middleware provides CSRF token validation
   - Cannot be exploited via URL manipulation

### Custom Developer Login Route
Our custom `/dev-login` route (separate from Spatie) provides security through:

1. **Route Registration**
   ```php
   if (app()->environment(['local', 'testing'])) {
       Route::get('/dev-login', DeveloperLoginController::class)->name('dev.login');
   }
   ```
   - Route only exists in local/testing environments

2. **Controller Check**
   ```php
   if (! app()->environment(['local', 'testing'])) {
       abort(404);
   }
   ```
   - Returns 404 in production as fallback

3. **Logging**
   - All login attempts logged with user ID, email, IP address

---

## Why Signed URLs Don't Apply

### Spatie Package Flow
```
1. User clicks login-link component
2. POST form submitted with CSRF token
3. Controller validates CSRF token
4. User authenticated
```

### Signed URL Flow (Not Used)
```
1. Generate signed URL with expiration
2. User clicks GET link
3. Middleware validates signature
4. User authenticated
```

The Spatie package uses the first flow (POST + CSRF), so signed URL middleware is not applicable.

---

## Two Separate Systems

| Feature | Spatie Login-Link | Custom dev.login |
|---------|-------------------|------------------|
| Method | POST | GET |
| Security | CSRF + Environment | Environment + Controller |
| Config | `config/login-link.php` | `routes/web.php` |
| Component | `<x-login-link>` | Direct URL |
| Signed URLs | ❌ Not applicable | ❌ Not required |

---

## Verification

```bash
# Check config
php artisan tinker --execute="dump(config('login-link.middleware'));"
# Expected: ['web']

# Test Spatie component (local only)
# Visit login page, click developer login button

# Test custom route (local only)
curl "http://app.crm.test/dev-login?email=user@example.com"
```

---

## Related Documentation

- [Developer Login Documentation](../auth/developer-login.md)
- [Environment Changes](../env-changes.md)
- [Deployment Checklist](../deploy.md)

---

*Updated: 2025-12-08*  
*Version: 2.0 (Supersedes v1.0)*
