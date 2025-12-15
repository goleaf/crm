# Blade Hot Refresh with Vite

## Overview
Blade Hot Refresh provides instant browser updates when Blade templates, Livewire components, or Filament resources change during development. This eliminates the need to manually refresh the browser, significantly improving developer experience.

## How It Works
Vite's built-in file watcher monitors specified file patterns and triggers a full page reload when changes are detected. Unlike traditional HMR (Hot Module Replacement) which only updates JavaScript/CSS, this refreshes the entire page to reflect server-side template changes.

## Configuration

### Vite Config (`vite.config.js`)
```javascript
laravel({
    input: [
        // Your entry points
    ],
    refresh: [
        // Blade templates
        'resources/views/**/*.blade.php',
        'app/Filament/**/*.php',
        'app/Livewire/**/*.php',
        'app-modules/**/resources/views/**/*.blade.php',
        // Routes
        'routes/**/*.php',
        // Config files that affect UI
        'config/filament.php',
        'config/app.php',
    ],
})
```

## Watched File Patterns

### Blade Templates
- `resources/views/**/*.blade.php` - All application Blade views
- `app-modules/**/resources/views/**/*.blade.php` - Module Blade views

### Filament Components
- `app/Filament/**/*.php` - All Filament resources, pages, widgets, actions
  - Resources (forms, tables, schemas)
  - Pages (custom pages, resource pages)
  - Widgets (dashboard widgets, stats)
  - Actions (table actions, form actions)
  - Clusters (navigation groups)

### Livewire Components
- `app/Livewire/**/*.php` - All Livewire components
  - Full-page components
  - Nested components
  - Form components

### Routes
- `routes/**/*.php` - All route files
  - `routes/web.php`
  - `routes/api.php`
  - `routes/console.php`
  - Module routes

### Configuration
- `config/filament.php` - Filament panel configuration
- `config/app.php` - Application configuration

## Usage

### Development Server
Start the Vite dev server:
```bash
npm run dev
```

Or use the full development stack:
```bash
composer dev
```

### What Triggers Refresh

#### ✅ Automatic Refresh
- Editing Blade templates
- Modifying Filament resource forms/tables
- Changing Livewire component render methods
- Updating route definitions
- Modifying Filament page schemas
- Changing widget configurations
- Updating action definitions

#### ❌ No Refresh Needed
- JavaScript changes (uses HMR)
- CSS changes (uses HMR)
- Asset changes (uses HMR)

## Developer Experience

### Before Hot Refresh
1. Edit Blade file
2. Switch to browser
3. Press Cmd+R (or F5)
4. Wait for page load
5. Navigate back to edited section

### With Hot Refresh
1. Edit Blade file
2. Save
3. Browser automatically refreshes
4. See changes instantly

## Performance Considerations

### File Watching
- Vite uses efficient file watchers (chokidar)
- Only watches specified patterns
- Minimal CPU/memory overhead
- Fast change detection (<100ms)

### Refresh Speed
- Full page reload (not HMR)
- Typical refresh: 200-500ms
- Depends on page complexity
- Faster than manual refresh

### Optimization Tips
1. **Limit watched patterns** - Only watch files that affect UI
2. **Exclude vendor files** - Don't watch `vendor/` or `node_modules/`
3. **Use specific patterns** - Avoid overly broad globs
4. **Close unused tabs** - Reduces browser memory usage

## Troubleshooting

### Refresh Not Working

#### Check Vite Dev Server
```bash
# Ensure Vite is running
npm run dev
```

#### Verify File Patterns
```javascript
// In vite.config.js
refresh: [
    'resources/views/**/*.blade.php', // Must match your file
]
```

#### Clear Vite Cache
```bash
rm -rf node_modules/.vite
npm run dev
```

### Too Many Refreshes

#### Narrow File Patterns
```javascript
// Too broad (refreshes on any PHP change)
refresh: ['**/*.php']

// Better (only UI-related files)
refresh: [
    'resources/views/**/*.blade.php',
    'app/Filament/**/*.php',
]
```

### Slow Refreshes

#### Check Browser Console
- Look for errors preventing page load
- Check network tab for slow requests
- Verify no infinite redirect loops

#### Optimize Application
- Use query caching
- Eager load relationships
- Minimize middleware
- Profile slow routes

## Integration with Existing Tools

### Works With
- ✅ Laravel Pail (log tailing)
- ✅ Filament v4.3+ (resources, pages, widgets)
- ✅ Livewire v3 (components)
- ✅ Tailwind CSS (via HMR)
- ✅ Vue 3 (via HMR)
- ✅ Playwright (E2E tests)

### Complements
- **Tailwind JIT** - CSS changes use HMR (no refresh)
- **Vue HMR** - Component changes use HMR (no refresh)
- **Livewire** - Template changes trigger refresh
- **Filament** - Resource changes trigger refresh

## Best Practices

### DO:
- ✅ Run Vite dev server during development
- ✅ Watch only UI-related files
- ✅ Use specific file patterns
- ✅ Keep browser DevTools open for debugging
- ✅ Use `composer dev` for full stack

### DON'T:
- ❌ Watch entire `app/` directory
- ❌ Watch `vendor/` or `node_modules/`
- ❌ Watch files that don't affect UI
- ❌ Forget to start Vite dev server
- ❌ Rely on refresh for JavaScript changes (use HMR)

## Advanced Configuration

### Custom Refresh Delay
```javascript
laravel({
    refresh: {
        paths: ['resources/views/**/*.blade.php'],
        config: { delay: 100 }, // Debounce refreshes
    },
})
```

### Conditional Watching
```javascript
const isDevelopment = process.env.NODE_ENV === 'development';

laravel({
    refresh: isDevelopment ? [
        'resources/views/**/*.blade.php',
        'app/Filament/**/*.php',
    ] : false,
})
```

### Module-Specific Patterns
```javascript
refresh: [
    // Core views
    'resources/views/**/*.blade.php',
    
    // Module views
    'app-modules/Documentation/resources/views/**/*.blade.php',
    'app-modules/SystemAdmin/resources/views/**/*.blade.php',
    
    // Filament resources
    'app/Filament/Resources/**/*.php',
    'app/Filament/Pages/**/*.php',
    'app/Filament/Widgets/**/*.php',
]
```

## Testing

### Verify Hot Refresh Works
1. Start Vite dev server: `npm run dev`
2. Open browser to your app
3. Edit a Blade file: `resources/views/welcome.blade.php`
4. Save the file
5. Browser should refresh automatically

### Test Different File Types
```bash
# Test Blade refresh
echo "<!-- Test -->" >> resources/views/welcome.blade.php

# Test Filament refresh
touch app/Filament/Resources/CompanyResource.php

# Test Livewire refresh
touch app/Livewire/Dashboard.php
```

## Related Documentation
- `vite.config.js` - Vite configuration
- `package.json` - NPM scripts
- `docs/laravel-pail.md` - Log tailing
- `docs/playwright-integration.md` - E2E testing
- `.kiro/steering/filament-conventions.md` - Filament patterns

## References
- [Laravel Vite Plugin](https://laravel.com/docs/vite)
- [Vite File Watching](https://vitejs.dev/config/server-options.html#server-watch)
- [Laravel News Article](https://laravel-news.com/laravel-blade-hot-refresh-with-vite)
- [Chokidar (File Watcher)](https://github.com/paulmillr/chokidar)

## Quick Reference

### Start Development
```bash
npm run dev
```

### Watched Patterns
- `resources/views/**/*.blade.php`
- `app/Filament/**/*.php`
- `app/Livewire/**/*.php`
- `app-modules/**/resources/views/**/*.blade.php`
- `routes/**/*.php`

### Refresh Behavior
- **Blade changes** → Full page reload
- **CSS changes** → HMR (no reload)
- **JS changes** → HMR (no reload)
- **Route changes** → Full page reload
- **Config changes** → Full page reload
