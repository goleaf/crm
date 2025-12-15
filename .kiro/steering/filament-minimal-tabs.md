---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/*.php"
  - "resources/views/filament/**/*.blade.php"
---

# Filament Minimal Tabs

## Core Principles
- Use `MinimalTabs` for cleaner, more compact tab interfaces in Filament v4.3+ forms.
- Minimal tabs reduce visual clutter while maintaining full functionality.
- Fully compatible with standard Filament tabs API.

## When to Use Minimal Tabs

### ✅ Use For:
- Settings pages with multiple sections (CRM Settings, User Preferences)
- Resource forms with 3+ logical groupings
- Dashboard pages with tabbed content
- Forms where space is limited
- Multi-step wizards with tab navigation
- Admin panels with dense information

### ❌ Don't Use For:
- Forms with only 1-2 sections (use Sections instead)
- Nested tab structures (use Sections within tabs)
- Single-field groups (use Fieldsets)
- Content that should always be visible

## Basic Usage

### Import and Use
```php
use App\Filament\Components\MinimalTabs;

MinimalTabs::make('Settings')
    ->tabs([
        MinimalTabs\Tab::make('General')
            ->icon('heroicon-o-cog')
            ->schema([...]),
        MinimalTabs\Tab::make('Advanced')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([...]),
    ])
```

### With Icons and Badges
```php
MinimalTabs::make('Dashboard')
    ->tabs([
        MinimalTabs\Tab::make('Tasks')
            ->icon('heroicon-o-clipboard-document-list')
            ->badge(fn () => Task::pending()->count())
            ->badgeColor('warning')
            ->schema([...]),
        MinimalTabs\Tab::make('Notifications')
            ->icon('heroicon-o-bell')
            ->badge('5')
            ->badgeColor('danger')
            ->schema([...]),
    ])
```

## Variants

### Compact Mode
For dense forms with limited space:
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

## State Persistence

### Query String Persistence
```php
MinimalTabs::make('Settings')
    ->persistTabInQueryString()
    ->tabs([...])
```

### Local Storage Persistence
```php
MinimalTabs::make('Settings')
    ->persistTabInLocalStorage()
    ->tabs([...])
```

## Best Practices

### DO:
- ✅ Use icons for better visual recognition
- ✅ Add badges to show counts or status
- ✅ Keep tab labels short (1-2 words)
- ✅ Group related fields logically
- ✅ Use compact mode for dense forms
- ✅ Persist tab state for better UX
- ✅ Limit to 8-10 tabs maximum

### DON'T:
- ❌ Nest tabs within tabs
- ❌ Use long tab labels that wrap
- ❌ Mix minimal and standard tabs
- ❌ Put single fields in tabs
- ❌ Create too many tabs (>10)

## Integration Patterns

### Settings Pages
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
                ])
                ->columnSpanFull(),
        ]);
}
```

### Resource Forms
```php
// app/Filament/Resources/CompanyResource.php
use App\Filament\Components\MinimalTabs;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            MinimalTabs::make('Company')
                ->tabs([
                    MinimalTabs\Tab::make('Basic')
                        ->icon('heroicon-o-building-office')
                        ->schema([...]),
                    MinimalTabs\Tab::make('Address')
                        ->icon('heroicon-o-map-pin')
                        ->schema([...]),
                ])
                ->columnSpanFull(),
        ]);
}
```

## Conditional Tabs

Show/hide tabs based on permissions or state:
```php
MinimalTabs::make('Settings')
    ->tabs([
        MinimalTabs\Tab::make('General')
            ->schema([...]),
        MinimalTabs\Tab::make('Admin')
            ->visible(fn () => auth()->user()->can('manage_settings'))
            ->schema([...]),
    ])
```

## Dynamic Badges

Update badge counts dynamically:
```php
MinimalTabs::make('Dashboard')
    ->tabs([
        MinimalTabs\Tab::make('Pending')
            ->badge(fn () => Task::pending()->count())
            ->badgeColor(fn ($badge) => $badge > 0 ? 'warning' : 'success')
            ->schema([...]),
    ])
```

## Styling

The minimal tabs component uses these CSS classes:
- `.minimal-tabs` - Main container
- `.minimal-tabs-list` - Tab header list
- `.minimal-tabs-tab` - Individual tab button
- `.minimal-tabs-content` - Tab content container
- `.minimal-tabs-compact` - Compact variant

Custom styling can be added to `resources/css/filament/admin/theme.css`.

## Migration from Standard Tabs

Replace standard tabs with minimal tabs:

```php
// Before
use Filament\Schemas\Components\Tabs;
Tabs::make('Settings')

// After
use App\Filament\Components\MinimalTabs;
MinimalTabs::make('Settings')
```

Tab definitions remain fully compatible.

## Performance

- Lightweight Alpine.js state management
- No additional HTTP requests
- Instant tab switching (content pre-rendered)
- CSS compiled with Tailwind (no runtime overhead)

## Accessibility

- Full ARIA support
- Keyboard navigation (arrow keys)
- Focus management
- Screen reader compatible

## Related Documentation
- `docs/filament-minimal-tabs.md` - Complete usage guide
- `.kiro/steering/filament-conventions.md` - Filament v4.3+ patterns
- `.kiro/steering/filament-content-layouts.md` - Layout best practices
