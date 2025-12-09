# Minimal Tabs - Quick Reference

## Import

```php
use App\Filament\Components\MinimalTabs;
```

## Basic Usage

```php
MinimalTabs::make('Settings')
    ->tabs([
        MinimalTabs\Tab::make('General')
            ->schema([...]),
        MinimalTabs\Tab::make('Advanced')
            ->schema([...]),
    ])
```

## With Icons

```php
MinimalTabs\Tab::make('General')
    ->icon('heroicon-o-cog')
    ->schema([...])
```

## With Badges

```php
MinimalTabs\Tab::make('Notifications')
    ->badge('5')
    ->badgeColor('danger')
    ->schema([...])
```

## Dynamic Badges

```php
MinimalTabs\Tab::make('Tasks')
    ->badge(fn () => Task::pending()->count())
    ->badgeColor(fn ($badge) => $badge > 0 ? 'warning' : 'success')
    ->schema([...])
```

## Compact Mode

```php
MinimalTabs::make('Settings')
    ->compact()
    ->tabs([...])
```

## Vertical Layout

```php
MinimalTabs::make('Settings')
    ->vertical()
    ->tabs([...])
```

## State Persistence

```php
// Query string
MinimalTabs::make('Settings')
    ->persistTabInQueryString()
    ->tabs([...])

// Local storage
MinimalTabs::make('Settings')
    ->persistTabInLocalStorage()
    ->tabs([...])
```

## Conditional Visibility

```php
MinimalTabs\Tab::make('Admin')
    ->visible(fn () => auth()->user()->isAdmin())
    ->schema([...])
```

## Full Example

```php
use App\Filament\Components\MinimalTabs;

MinimalTabs::make('Article')
    ->tabs([
        MinimalTabs\Tab::make(__('app.labels.content'))
            ->icon('heroicon-o-document-text')
            ->schema([
                TextInput::make('title')->required(),
                RichEditor::make('content')->required(),
            ]),
        MinimalTabs\Tab::make(__('app.labels.settings'))
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Select::make('status')->required(),
                Toggle::make('featured'),
            ]),
        MinimalTabs\Tab::make(__('app.labels.attachments'))
            ->icon('heroicon-o-paper-clip')
            ->badge(fn ($record) => $record?->attachments->count())
            ->schema([
                FileUpload::make('attachments')->multiple(),
            ]),
    ])
    ->columnSpanFull()
    ->persistTabInQueryString()
```

## Common Icons

- `heroicon-o-document-text` - Content/Details
- `heroicon-o-cog-6-tooth` - Settings
- `heroicon-o-user` - Profile
- `heroicon-o-user-group` - Assignments/Team
- `heroicon-o-clock` - Time/Schedule
- `heroicon-o-paper-clip` - Attachments
- `heroicon-o-tag` - Tags
- `heroicon-o-adjustments-horizontal` - Custom Fields
- `heroicon-o-link` - Integrations
- `heroicon-o-shield-check` - Security/Quality
- `heroicon-o-check-badge` - Qualification
- `heroicon-o-sparkles` - Nurturing
- `heroicon-o-magnifying-glass` - SEO/Search

## Badge Colors

- `primary` - Default
- `success` - Green
- `warning` - Orange
- `danger` - Red
- `info` - Blue
- `gray` - Neutral

## CSS Classes

- `.minimal-tabs` - Main container
- `.minimal-tabs-list` - Tab header list
- `.minimal-tabs-tab` - Individual tab button
- `.minimal-tabs-content` - Tab content container
- `.minimal-tabs-compact` - Compact variant

## When to Use

### ✅ Use For:
- Forms with 3+ sections
- Settings pages
- Resource forms with logical groups
- Dashboard pages with tabs
- Multi-step wizards

### ❌ Don't Use For:
- Forms with 1-2 sections
- Nested tab structures
- Single-field groups
- Always-visible content

## Migration from Standard Tabs

```php
// Before
use Filament\Forms\Components\Tabs;
Tabs::make('Settings')

// After
use App\Filament\Components\MinimalTabs;
MinimalTabs::make('Settings')
```

## Troubleshooting

### Tabs not showing
```bash
npm run build
```

### Styling issues
```bash
php artisan filament:clear-cached-components
```

## Documentation

- Complete Guide: `docs/filament-minimal-tabs.md`
- Steering Rule: `.kiro/steering/filament-minimal-tabs.md`
- Final Report: `MINIMAL_TABS_FINAL_REPORT.md`
