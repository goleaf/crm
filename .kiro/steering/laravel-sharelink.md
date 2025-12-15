# Laravel ShareLink Integration

> **📚 Comprehensive Guide**: See `docs/laravel-sharelink-integration.md` for complete usage patterns, security features, and Filament integration examples.

## Core Principles
- Laravel ShareLink provides secure, temporary shareable links for any Eloquent model.
- All link operations go through `ShareLinkService` (singleton) for consistency and caching.
- Links support expiration, password protection, click limits, and burn-after-reading.
- User tracking enabled by default; links are attributed to creators.
- Filament resource provides full admin UI for link management.

## Service Usage
- Use `ShareLinkService` (singleton) for all link operations instead of direct model access.
- Inject via constructor: `public function __construct(private readonly ShareLinkService $shareLink) {}`.
- All methods return cached results (1-hour TTL by default); cache keys follow `sharelinks.*` pattern.
- Call `$shareLink->clearCache()` after bulk operations or when cache invalidation is needed.

## Link Creation Patterns

### Basic Link
```php
$link = $shareLink->createLink($model);
```

### Temporary Link (Expires in X hours)
```php
$link = $shareLink->createTemporaryLink($model, hours: 24);
```

### One-Time Link (Burn After Reading)
```php
$link = $shareLink->createOneTimeLink($model);
```

### Password-Protected Link
```php
$link = $shareLink->createProtectedLink($model, 'password123');
```

### Advanced Options
```php
$link = $shareLink->createLink(
    model: $model,
    expiresAt: now()->addWeek(),
    maxClicks: 100,
    password: 'secret',
    metadata: ['team_id' => $teamId, 'purpose' => 'review']
);
```

## Link Management

### Check Status
```php
$isActive = $shareLink->isLinkActive($link);
$stats = $shareLink->getLinkStats($link);
```

### Revoke Links
```php
$shareLink->revokeLink($link);
```

### Extend Expiration
```php
$shareLink->extendLink($link, now()->addMonth());
```

### Get Links
```php
$activeLinks = $shareLink->getActiveLinksForModel($model);
$userLinks = $shareLink->getUserLinks($userId);
$teamLinks = $shareLink->getTeamLinks($teamId);
```

## Filament Integration
- Resource available at System → Share Links
- Features: list, view, copy URL, extend, revoke, bulk actions
- Statistics modal shows global link metrics
- Automatic user scoping (users see only their links unless `view_all_sharelinks` permission)
- Access control via `view_sharelinks`, `create_sharelinks`, `update_sharelinks`, `delete_sharelinks` permissions

## Configuration
- Config file: `config/sharelink.php`
- User tracking enabled by default: `SHARELINK_USER_TRACKING_ENABLED=true`
- Cache TTL: `SHARELINK_CACHE_TTL=3600`
- Burn after reading: `SHARELINK_BURN_ENABLED=true`
- Signed URLs: `SHARELINK_SIGNED_ENABLED=true`
- Rate limiting: `SHARELINK_RATE_ENABLED=false` (enable in production)

## Security Features
- **Password Protection**: Optional password for sensitive links
- **Expiration**: Time-based or click-based expiration
- **IP Restrictions**: Allow/deny lists in config
- **Rate Limiting**: Prevent abuse (configurable)
- **Signed URLs**: Optional signature verification
- **Burn After Reading**: Auto-revoke after first access
- **User Attribution**: Track who created each link

## Testing
- Mock `ShareLinkService` in unit tests to avoid database queries
- Use `ShareLink::factory()` for test data (if factory exists)
- Test link creation, expiration, revocation, and access
- Verify cache behavior with `Cache::fake()`

## Translations
- Navigation: `__('app.navigation.share_links')`
- Labels: `__('app.labels.share_link')`, `__('app.labels.token')`, `__('app.labels.expires_at')`
- Actions: `__('app.actions.copy_url')`, `__('app.actions.extend')`, `__('app.actions.revoke')`
- Notifications: `__('app.notifications.link_extended')`, `__('app.notifications.link_revoked')`
- Helpers: `__('app.helpers.max_clicks')`, `__('app.helpers.sharelink_password')`

## Best Practices
- ✅ Use service methods instead of direct model access
- ✅ Set appropriate expiration times for security
- ✅ Use password protection for sensitive content
- ✅ Add metadata for tracking and filtering
- ✅ Clear cache after bulk operations
- ✅ Monitor link usage via statistics
- ✅ Revoke links when no longer needed
- ✅ Use one-time links for highly sensitive data
- ❌ Don't create links without expiration for sensitive data
- ❌ Don't share links via insecure channels
- ❌ Don't forget to revoke links after use
- ❌ Don't skip password protection for confidential content
- ❌ Don't create links directly without the service layer

## Maintenance
- Automatic pruning runs daily at 3:00 AM (configurable)
- Manual pruning: `php artisan sharelink:prune`
- Clear cache: `$shareLink->clearCache()`
- Monitor statistics in Filament UI

## Related Documentation
- `docs/laravel-sharelink-integration.md` - Complete integration guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-conventions.md` - Filament integration patterns
- `.kiro/steering/testing-standards.md` - Testing conventions

## Package Information
- Package: `grazulex/laravel-sharelink` v1.2.0
- Service: `App\Services\ShareLink\ShareLinkService`
- Resource: `App\Filament\Resources\ShareLinkResource`
- Model: `Grazulex\ShareLink\Models\ShareLink`
- Config: `config/sharelink.php`
- Migration: `database/migrations/2025_12_09_191910_create_share_links_table.php`
