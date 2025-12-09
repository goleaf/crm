# Laravel ShareLink - Quick Start Guide

## Installation Complete ✅

The Laravel ShareLink package is fully integrated and ready to use.

## Quick Usage

### Create a Shareable Link

```php
use App\Services\ShareLink\ShareLinkService;

$service = app(ShareLinkService::class);

// Basic link
$link = $service->createLink($company);
$url = route('sharelink.show', ['token' => $link->token]);

// Temporary link (expires in 24 hours)
$link = $service->createTemporaryLink($company, hours: 24);

// One-time link (burn after reading)
$link = $service->createOneTimeLink($document);

// Password-protected link
$link = $service->createProtectedLink($invoice, 'secret123');
```

### Access the Link

Share the URL with users:
```
https://your-app.com/share/{token}
```

### Manage Links in Filament

Navigate to: **System → Share Links**

Features:
- View all share links
- Copy URLs to clipboard
- Extend expiration dates
- Revoke links
- View statistics

## Common Patterns

### Share a Company Profile

```php
public function shareCompany(Company $company)
{
    $service = app(ShareLinkService::class);
    
    $link = $service->createTemporaryLink(
        model: $company,
        hours: 48,
        metadata: [
            'team_id' => auth()->user()->currentTeam->id,
            'shared_by' => auth()->user()->name,
            'purpose' => 'client_review',
        ]
    );
    
    return [
        'url' => route('sharelink.show', ['token' => $link->token]),
        'expires_at' => $link->expires_at,
    ];
}
```

### Share a Document with Password

```php
public function shareDocument(Document $document, string $password)
{
    $service = app(ShareLinkService::class);
    
    $link = $service->createProtectedLink(
        model: $document,
        password: $password,
        expiresAt: now()->addWeek(),
        maxClicks: 10
    );
    
    return route('sharelink.show', ['token' => $link->token]);
}
```

### Check Link Status

```php
$service = app(ShareLinkService::class);

if ($service->isLinkActive($link)) {
    // Link is still valid
    $stats = $service->getLinkStats($link);
    
    echo "Clicks: {$stats['total_clicks']}";
    echo "Remaining: {$stats['remaining_clicks']}";
    echo "Expires in: {$stats['days_until_expiry']} days";
}
```

### Revoke All Links for a Model

```php
$service = app(ShareLinkService::class);
$activeLinks = $service->getActiveLinksForModel($company);

foreach ($activeLinks as $link) {
    $service->revokeLink($link);
}
```

## Configuration

### Environment Variables

```env
SHARELINK_USER_TRACKING_ENABLED=true
SHARELINK_CACHE_TTL=3600
SHARELINK_BURN_ENABLED=true
SHARELINK_SIGNED_ENABLED=true
```

### Common Settings

Edit `config/sharelink.php`:

```php
// Enable burn after reading
'burn' => [
    'enabled' => true,
    'strategy' => 'revoke', // or 'delete'
],

// Set rate limits
'limits' => [
    'rate' => [
        'enabled' => true,
        'max' => 60,
        'decay' => 60,
    ],
],
```

## Security Best Practices

1. **Always set expiration** for sensitive content
2. **Use password protection** for confidential data
3. **Use one-time links** for highly sensitive information
4. **Monitor link usage** via Filament statistics
5. **Revoke links** when no longer needed
6. **Enable rate limiting** in production

## Filament Actions

Add share functionality to your resources:

```php
use App\Services\ShareLink\ShareLinkService;

Tables\Actions\Action::make('share')
    ->label(__('app.actions.share'))
    ->icon('heroicon-o-share')
    ->form([
        Forms\Components\TextInput::make('hours')
            ->label(__('app.labels.expires_in_hours'))
            ->numeric()
            ->default(24)
            ->required(),
        Forms\Components\TextInput::make('password')
            ->label(__('app.labels.password'))
            ->password()
            ->revealable(),
    ])
    ->action(function ($record, array $data, ShareLinkService $service) {
        $link = $service->createTemporaryLink(
            model: $record,
            hours: $data['hours'],
            password: $data['password'] ?? null
        );
        
        $url = route('sharelink.show', ['token' => $link->token]);
        
        \Filament\Notifications\Notification::make()
            ->title(__('app.notifications.link_created'))
            ->body($url)
            ->success()
            ->send();
    })
    ->color('gray');
```

## Troubleshooting

### Link Not Working?

1. Check if expired: `$link->expires_at`
2. Check if revoked: `$link->revoked_at`
3. Check click limit: `$link->click_count >= $link->max_clicks`

### Performance Issues?

1. Enable caching: `SHARELINK_CACHE_TTL=3600`
2. Use Redis for cache driver
3. Enable rate limiting

## Next Steps

- Read full documentation: `docs/laravel-sharelink-integration.md`
- Check steering file: `.kiro/steering/laravel-sharelink.md`
- View integration summary: `LARAVEL_SHARELINK_INTEGRATION_COMPLETE.md`

## Support

For issues or questions:
- Package: https://github.com/grazulex/laravel-sharelink
- Documentation: `docs/laravel-sharelink-integration.md`
