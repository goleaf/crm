# Laravel ShareLink Integration

## Overview

Laravel ShareLink (`grazulex/laravel-sharelink`) provides secure, temporary shareable links for any model in your application. This integration includes a service layer, Filament resource, and comprehensive management features.

## Features

- **Flexible Link Generation**: Create shareable links for any Eloquent model
- **Expiration Control**: Set expiration dates or max click counts
- **Password Protection**: Optional password protection for sensitive content
- **One-Time Links**: Burn-after-reading functionality
- **Usage Tracking**: Track clicks, IPs, and access patterns
- **User Attribution**: Track who created each link (when enabled)
- **Team Scoping**: Support for multi-tenant applications
- **Filament Integration**: Full admin UI for managing links

## Installation

The package is already installed and configured. The migration has been run and the service is registered.

### Configuration

Configuration is located in `config/sharelink.php`. Key settings:

```php
// Enable user tracking
'user_tracking' => [
    'enabled' => true,
    'user_id_type' => 'bigint',
    'user_table' => 'users',
],

// Cache TTL for service layer
'cache_ttl' => 3600,

// Burn after reading
'burn' => [
    'enabled' => true,
    'strategy' => 'revoke', // or 'delete'
],

// Rate limiting
'limits' => [
    'rate' => [
        'enabled' => false,
        'max' => 60,
        'decay' => 60,
    ],
],
```

### Environment Variables

```env
SHARELINK_USER_TRACKING_ENABLED=true
SHARELINK_CACHE_TTL=3600
SHARELINK_BURN_ENABLED=true
SHARELINK_SIGNED_ENABLED=true
```

## Service Usage

### Basic Usage

```php
use App\Services\ShareLink\ShareLinkService;
use App\Models\Company;

$service = app(ShareLinkService::class);
$company = Company::find(1);

// Create a basic shareable link
$link = $service->createLink($company);

// Access the URL
$url = route('sharelink.show', ['token' => $link->token]);
```

### Temporary Links

```php
// Expires in 24 hours (default)
$link = $service->createTemporaryLink($company);

// Expires in 7 days
$link = $service->createTemporaryLink($company, hours: 168);

// Expires in 2 hours with max 10 clicks
$link = $service->createTemporaryLink(
    model: $company,
    hours: 2,
    maxClicks: 10
);
```

### One-Time Links (Burn After Reading)

```php
// Link is automatically revoked after first access
$link = $service->createOneTimeLink($company);

// With custom expiration
$link = $service->createOneTimeLink(
    model: $company,
    expiresAt: now()->addDays(3)
);
```

### Password-Protected Links

```php
$link = $service->createProtectedLink(
    model: $company,
    password: 'secret123',
    expiresAt: now()->addWeek()
);
```

### Advanced Options

```php
use Illuminate\Support\Carbon;

$link = $service->createLink(
    model: $company,
    expiresAt: Carbon::parse('2025-12-31 23:59:59'),
    maxClicks: 100,
    password: 'secure-password',
    metadata: [
        'team_id' => auth()->user()->currentTeam->id,
        'purpose' => 'client_review',
        'department' => 'sales',
    ]
);
```

## Link Management

### Check Link Status

```php
$isActive = $service->isLinkActive($link);

$stats = $service->getLinkStats($link);
// Returns:
// [
//     'total_clicks' => 15,
//     'remaining_clicks' => 85,
//     'is_expired' => false,
//     'is_revoked' => false,
//     'is_active' => true,
//     'first_accessed' => '2025-12-09 10:30:00',
//     'last_accessed' => '2025-12-09 15:45:00',
//     'days_until_expiry' => 7,
// ]
```

### Revoke Links

```php
// Revoke a single link
$service->revokeLink($link);

// Get all active links for a model
$activeLinks = $service->getActiveLinksForModel($company);

// Revoke all links for a model
foreach ($activeLinks as $link) {
    $service->revokeLink($link);
}
```

### Extend Expiration

```php
$service->extendLink($link, now()->addMonth());
```

### Get User's Links

```php
// Get all links created by a user
$userLinks = $service->getUserLinks(auth()->id());

// Get team's links
$teamLinks = $service->getTeamLinks($teamId);
```

### Global Statistics

```php
$stats = $service->getGlobalStats();
// Returns:
// [
//     'total_links' => 1250,
//     'active_links' => 450,
//     'expired_links' => 300,
//     'revoked_links' => 500,
//     'total_clicks' => 15000,
//     'average_clicks' => 12.0,
// ]
```

## Filament Integration

### Resource Location

The ShareLink resource is available at `/app/share-links` in the Filament admin panel.

### Features

- **List View**: View all share links with filtering and search
- **View Page**: Detailed information about each link
- **Statistics Modal**: Global statistics dashboard
- **Actions**:
  - Copy URL to clipboard
  - Extend expiration date
  - Revoke link
  - Bulk revoke multiple links

### Filters

- **Active/Inactive**: Filter by link status
- **Password Protected**: Show only protected links
- **Expires Soon**: Links expiring within 7 days

### Permissions

The resource respects user permissions:
- Users can only see their own links (unless they have `view_all_sharelinks` permission)
- All actions are logged with user attribution

## Model Integration

### Adding ShareLink Support to Models

ShareLink works with any Eloquent model out of the box. No trait is required.

```php
use App\Services\ShareLink\ShareLinkService;

// In your controller or service
$service = app(ShareLinkService::class);
$link = $service->createLink($model);
```

### Example: Company Sharing

```php
// In CompanyResource or CompanyController
use App\Services\ShareLink\ShareLinkService;

public function share(Company $company, ShareLinkService $service)
{
    $link = $service->createTemporaryLink(
        model: $company,
        hours: 48,
        metadata: [
            'team_id' => auth()->user()->currentTeam->id,
            'shared_by' => auth()->user()->name,
        ]
    );

    return response()->json([
        'url' => route('sharelink.show', ['token' => $link->token]),
        'expires_at' => $link->expires_at->toDateTimeString(),
    ]);
}
```

## Security Features

### Password Protection

```php
$link = $service->createProtectedLink(
    model: $document,
    password: 'secure-password-123'
);
```

Users accessing the link will be prompted for the password.

### IP Restrictions

Configure IP allow/deny lists in `config/sharelink.php`:

```php
'limits' => [
    'ip' => [
        'allow' => ['192.168.1.0/24'], // Only these IPs
        'deny' => ['10.0.0.5'],        // Block these IPs
    ],
],
```

### Rate Limiting

```php
'limits' => [
    'rate' => [
        'enabled' => true,
        'max' => 60,      // Max attempts
        'decay' => 60,    // Per 60 seconds
    ],
],
```

### Signed URLs

```php
'signed' => [
    'enabled' => true,
    'required' => false, // Set to true to require signed URLs
    'ttl' => 15,         // Minutes
],
```

## Accessing Shared Links

### Public Route

Links are accessed via: `https://your-app.com/share/{token}`

### Custom Controller

You can customize the share link controller by extending the package's controller:

```php
namespace App\Http\Controllers;

use Grazulex\ShareLink\Http\Controllers\ShareLinkController as BaseController;

class ShareLinkController extends BaseController
{
    protected function handleResource($resource)
    {
        // Custom logic for handling the shared resource
        // $resource is the original model
        
        return view('shared.resource', [
            'resource' => $resource,
        ]);
    }
}
```

## Testing

### Unit Tests

```php
use App\Services\ShareLink\ShareLinkService;
use App\Models\Company;

it('creates a shareable link', function () {
    $service = app(ShareLinkService::class);
    $company = Company::factory()->create();
    
    $link = $service->createLink($company);
    
    expect($link->token)->not->toBeEmpty();
    expect($link->resource['type'])->toBe(Company::class);
    expect($link->resource['id'])->toBe($company->id);
});

it('creates a temporary link with expiration', function () {
    $service = app(ShareLinkService::class);
    $company = Company::factory()->create();
    
    $link = $service->createTemporaryLink($company, hours: 24);
    
    expect($link->expires_at)->not->toBeNull();
    expect($link->expires_at->diffInHours(now()))->toBe(24);
});

it('revokes a link', function () {
    $service = app(ShareLinkService::class);
    $company = Company::factory()->create();
    $link = $service->createLink($company);
    
    $service->revokeLink($link);
    
    expect($link->fresh()->revoked_at)->not->toBeNull();
    expect($service->isLinkActive($link->fresh()))->toBeFalse();
});
```

### Feature Tests

```php
use Grazulex\ShareLink\Models\ShareLink;

it('can access a valid share link', function () {
    $company = Company::factory()->create();
    $link = ShareLink::createForResource($company);
    
    $response = $this->get(route('sharelink.show', ['token' => $link->token]));
    
    $response->assertOk();
});

it('cannot access an expired link', function () {
    $company = Company::factory()->create();
    $link = ShareLink::createForResource($company);
    $link->expires_at = now()->subDay();
    $link->save();
    
    $response = $this->get(route('sharelink.show', ['token' => $link->token]));
    
    $response->assertForbidden();
});
```

## Best Practices

### DO:
- ✅ Use the service layer for all link operations
- ✅ Set appropriate expiration times
- ✅ Use password protection for sensitive content
- ✅ Add metadata for tracking and filtering
- ✅ Clear cache after bulk operations
- ✅ Monitor link usage via statistics
- ✅ Revoke links when no longer needed
- ✅ Use one-time links for highly sensitive data

### DON'T:
- ❌ Create links without expiration for sensitive data
- ❌ Share links via insecure channels
- ❌ Forget to revoke links after use
- ❌ Skip password protection for confidential content
- ❌ Ignore rate limiting in production
- ❌ Create links directly without the service layer

## Maintenance

### Pruning Expired Links

The package automatically schedules a prune command:

```php
// Runs daily at 3:00 AM by default
'schedule' => [
    'prune' => [
        'enabled' => true,
        'expression' => '0 3 * * *',
    ],
],
```

Manual pruning:

```bash
php artisan sharelink:prune
```

### Monitoring

Check link statistics in Filament:
1. Navigate to System → Share Links
2. Click "View Statistics" button
3. Review active, expired, and revoked links

### Cache Management

```php
// Clear all ShareLink caches
$service->clearCache();

// Clear cache for specific link
$service->clearCache($link);
```

## Troubleshooting

### Links Not Working

1. Check if the link is expired: `$link->expires_at`
2. Check if the link is revoked: `$link->revoked_at`
3. Check click count: `$link->click_count >= $link->max_clicks`
4. Verify route is registered: `php artisan route:list | grep sharelink`

### Performance Issues

1. Enable caching: `SHARELINK_CACHE_TTL=3600`
2. Use Redis for cache driver
3. Add database indexes (already included in migration)
4. Enable rate limiting to prevent abuse

### Security Concerns

1. Enable signed URLs: `SHARELINK_SIGNED_REQUIRED=true`
2. Set short expiration times for sensitive content
3. Use password protection
4. Configure IP restrictions
5. Monitor access logs

## Related Documentation

- Package Repository: https://github.com/grazulex/laravel-sharelink
- Service Pattern: `docs/laravel-container-services.md`
- Filament Integration: `.kiro/steering/filament-conventions.md`
- Testing Standards: `.kiro/steering/testing-standards.md`

## Integration Points

- Works with any Eloquent model
- Integrates with Filament v4.3+ admin panel
- Supports multi-tenancy via metadata
- Compatible with Laravel 12+
- Uses service container pattern
- Follows translation conventions
- Includes comprehensive logging

## Quick Reference

### Common Patterns

```php
// Temporary link (24 hours)
$link = $service->createTemporaryLink($model);

// One-time link
$link = $service->createOneTimeLink($model);

// Protected link
$link = $service->createProtectedLink($model, 'password');

// Check if active
$isActive = $service->isLinkActive($link);

// Get statistics
$stats = $service->getLinkStats($link);

// Revoke link
$service->revokeLink($link);

// Extend expiration
$service->extendLink($link, now()->addWeek());
```

### Filament Actions

- Copy URL to clipboard
- Extend expiration date
- Revoke link
- View detailed statistics
- Bulk revoke multiple links

### Environment Variables

```env
SHARELINK_USER_TRACKING_ENABLED=true
SHARELINK_CACHE_TTL=3600
SHARELINK_BURN_ENABLED=true
SHARELINK_SIGNED_ENABLED=true
SHARELINK_RATE_ENABLED=false
```
