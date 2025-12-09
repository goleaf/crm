# Filament Minimal Tabs Integration

## Overview

Since the `venturedrake/filament-minimal-tabs` package is not compatible with Filament v4.3+, we've created a custom implementation that provides similar functionality with a cleaner, more compact tab interface.

## Features

- **Minimal Styling**: Reduced visual clutter with cleaner tab headers
- **Compact Mode**: Optional compact variant with reduced spacing
- **Icon Support**: Add icons to tab headers
- **Badge Support**: Display counts or status badges on tabs
- **Vertical Layout**: Support for vertical tab orientation
- **Responsive Design**: Works seamlessly on all screen sizes
- **Filament v4.3+ Compatible**: Built specifically for the latest Filament version

## Installation

The minimal tabs component is already installed in your application:

- Component: `app/Filament/Components/MinimalTabs.php`
- View: `resources/views/filament/components/minimal-tabs.blade.php`
- Styles: `resources/css/filament/admin/theme.css`

## Basic Usage

### Simple Tabs

```php
use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\TextInput;

MinimalTabs::make('Settings')
    ->tabs([
        MinimalTabs\Tab::make('General')
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email(),
            ]),
        MinimalTabs\Tab::make('Advanced')
            ->schema([
                TextInput::make('api_key'),
            ]),
    ])
```

### Tabs with Icons

```php
MinimalTabs::make('Profile')
    ->tabs([
        MinimalTabs\Tab::make('Personal')
            ->icon('heroicon-o-user')
            ->schema([...]),
        MinimalTabs\Tab::make('Security')
            ->icon('heroicon-o-shield-check')
            ->schema([...]),
    ])
```

### Tabs with Badges

```php
MinimalTabs::make('Dashboard')
    ->tabs([
        MinimalTabs\Tab::make('Notifications')
            ->icon('heroicon-o-bell')
            ->badge('5')
            ->badgeColor('danger')
            ->schema([...]),
        MinimalTabs\Tab::make('Messages')
            ->icon('heroicon-o-envelope')
            ->badge('12')
            ->badgeColor('primary')
            ->schema([...]),
    ])
```

### Compact Mode

For forms with limited space, use the compact variant:

```php
MinimalTabs::make('Quick Settings')
    ->compact()
    ->tabs([...])
```

### Vertical Tabs

For sidebar-style navigation:

```php
MinimalTabs::make('Settings')
    ->vertical()
    ->tabs([...])
```

## Advanced Features

### Persistent Tabs

Tabs can persist their state in the query string or local storage:

```php
MinimalTabs::make('Settings')
    ->persistTabInQueryString()
    ->tabs([...])

// Or use local storage
MinimalTabs::make('Settings')
    ->persistTabInLocalStorage()
    ->tabs([...])
```

### Conditional Tabs

Show/hide tabs based on conditions:

```php
MinimalTabs::make('Settings')
    ->tabs([
        MinimalTabs\Tab::make('General')
            ->schema([...]),
        MinimalTabs\Tab::make('Admin')
            ->visible(fn () => auth()->user()->isAdmin())
            ->schema([...]),
    ])
```

### Dynamic Badge Counts

Update badge counts dynamically:

```php
MinimalTabs::make('Dashboard')
    ->tabs([
        MinimalTabs\Tab::make('Tasks')
            ->badge(fn () => Task::where('status', 'pending')->count())
            ->badgeColor(fn ($badge) => $badge > 0 ? 'warning' : 'success')
            ->schema([...]),
    ])
```

## Styling Customization

### CSS Classes

The minimal tabs component uses the following CSS classes:

- `.minimal-tabs` - Main container
- `.minimal-tabs-list` - Tab header list
- `.minimal-tabs-tab` - Individual tab button
- `.minimal-tabs-content` - Tab content container
- `.minimal-tabs-panel` - Individual tab panel
- `.minimal-tabs-compact` - Compact variant modifier

### Custom Styling

You can customize the appearance by adding CSS to your theme:

```css
/* Custom tab colors */
.minimal-tabs-tab {
    @apply text-blue-600 dark:text-blue-400;
}

/* Custom active state */
.minimal-tabs-tab[aria-selected="true"] {
    @apply border-blue-600 dark:border-blue-400;
}

/* Custom hover effect */
.minimal-tabs-tab:hover {
    @apply bg-blue-50 dark:bg-blue-900/20;
}
```

## Integration Examples

### CRM Settings Page

The CRM Settings page uses minimal tabs for a cleaner interface:

```php
// app/Filament/Pages/CrmSettings.php
use App\Filament\Components\MinimalTabs;

public function form(Form $form): Form
{
    return $form
        ->schema([
            MinimalTabs::make('Settings')
                ->tabs([
                    $this->getCompanyTab(),
                    $this->getLocaleTab(),
                    $this->getCurrencyTab(),
                    $this->getBusinessHoursTab(),
                    $this->getEmailTab(),
                    $this->getNotificationsTab(),
                    $this->getFeaturesTab(),
                    $this->getSecurityTab(),
                ])
                ->columnSpanFull(),
        ])
        ->statePath('data');
}
```

### Resource Forms

Use minimal tabs in resource forms for better organization:

```php
// app/Filament/Resources/CompanyResource.php
use App\Filament\Components\MinimalTabs;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            MinimalTabs::make('Company Details')
                ->tabs([
                    MinimalTabs\Tab::make('Basic Info')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('email')->email(),
                        ]),
                    MinimalTabs\Tab::make('Address')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            TextInput::make('street'),
                            TextInput::make('city'),
                        ]),
                    MinimalTabs\Tab::make('Settings')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Toggle::make('active'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
}
```

## Best Practices

### DO:
- ✅ Use minimal tabs for forms with 3+ logical sections
- ✅ Add icons to tabs for better visual recognition
- ✅ Use badges to show counts or status
- ✅ Keep tab labels short and descriptive
- ✅ Group related fields within tabs
- ✅ Use compact mode for dense forms
- ✅ Persist tab state for better UX

### DON'T:
- ❌ Use too many tabs (max 8-10 recommended)
- ❌ Nest tabs within tabs (use sections instead)
- ❌ Put single fields in tabs (use sections)
- ❌ Use long tab labels that wrap
- ❌ Mix minimal and standard tabs in the same form

## Performance Considerations

- Minimal tabs use Alpine.js for state management (lightweight)
- Tab content is rendered but hidden with `x-show` (instant switching)
- No additional HTTP requests or JavaScript bundles required
- CSS is compiled with Tailwind (no runtime overhead)

## Accessibility

The minimal tabs component follows ARIA best practices:

- Proper `role="tablist"` and `role="tab"` attributes
- Keyboard navigation support (arrow keys)
- Focus management
- Screen reader announcements
- Proper `aria-selected` and `aria-controls` attributes

## Migration from Standard Tabs

To migrate from standard Filament tabs to minimal tabs:

1. Replace the import:
   ```php
   // Before
   use Filament\Forms\Components\Tabs;
   
   // After
   use App\Filament\Components\MinimalTabs;
   ```

2. Update the component:
   ```php
   // Before
   Tabs::make('Settings')
   
   // After
   MinimalTabs::make('Settings')
   ```

3. Tab definitions remain the same (fully compatible)

## Troubleshooting

### Tabs not showing

Ensure you've compiled the CSS:
```bash
npm run build
```

### Styling issues

Clear the Filament cache:
```bash
php artisan filament:clear-cached-components
```

### Alpine.js errors

Check browser console for JavaScript errors and ensure Alpine.js is loaded.

## Related Documentation

- [Filament Forms Documentation](https://filamentphp.com/docs/4.x/forms/layout/tabs)
- [Filament v4.3+ Conventions](.kiro/steering/filament-conventions.md)
- [Filament Content Layouts](.kiro/steering/filament-content-layouts.md)

## Future Enhancements

Potential improvements for the minimal tabs component:

- [ ] Animated tab transitions
- [ ] Drag-and-drop tab reordering
- [ ] Collapsible tabs for mobile
- [ ] Tab groups/categories
- [ ] Custom tab templates
- [ ] Tab loading states
- [ ] Tab validation indicators
