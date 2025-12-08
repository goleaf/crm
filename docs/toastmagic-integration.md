# ToastMagic integration

## What changed
- Added `devrabiul/laravel-toaster-magic` (`^1.5`) for framework-wide toast notifications with Livewire v3 support.
- Configured `config/laravel-toaster-magic.php` for material theme, gradient + color mode, Livewire v3 hooks, and top-end positioning.
- Injected ToastMagic assets into Filament via render hooks (`STYLES_AFTER` / `SCRIPTS_AFTER`) and the public guest layout so toasts are available in both admin and marketing surfaces.
- Added `resources/views/toastmagic/{styles,scripts}.blade.php` with a small listener for the `toastMagic` DOM event so Livewire dispatches render toasts without extra JS.
- Introduced `App\Support\ToastMagic\ToastMagicNotifier` to emit both ToastMagic toasts and Filament notifications from PHP.

## Usage
```php
use App\Support\ToastMagic\ToastMagicNotifier;

// Controllers / services
ToastMagicNotifier::success(__('app.actions.saved'), __('app.messages.saved'));
ToastMagicNotifier::error(__('app.messages.unexpected_error'));

// Filament page / action closures
ToastMagicNotifier::warning(
    __('app.messages.check_inputs'),
    __('app.messages.correct_and_retry'),
);
```

### Livewire (Filament-compatible)
```php
// Inside a Livewire component
$this->dispatch(
    'toastMagic',
    status: 'success',    // success|info|warning|error
    title: __('app.messages.saved'),
    message: __('app.messages.saved_details'),
    options: ['showCloseBtn' => true],
);
```

## Configuration
- Tweak defaults in `config/laravel-toaster-magic.php` (position, timeouts, theme, gradient/color mode, Livewire toggle).
- Assets are auto-copied to `public/packages/devrabiul/laravel-toaster-magic` via the package’s asset service provider; no manual publish step needed.

## Notes
- Render hooks keep Filament layouts unchanged while enabling ToastMagic in every panel view.
- The helper keeps existing Filament notifications in sync so workflows relying on `Notification::make()->send()` continue to surface messages.
