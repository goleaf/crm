# Squeaky profanity validation

Squeaky (`jonpurvis/squeaky`) is installed to keep user-entered text free from profanity in forms, APIs, and Filament resources.

## Configuration
- Config file: `config/squeaky.php`.
- Env flags: `SQUEAKY_LOCALES` (comma-separated, default `en`), `SQUEAKY_FALLBACK_LOCALE` (default `en`).
- Customize `blocked_words` and `allowed_words` for tenant/brand-specific terms; `case_sensitive` stays `false` for safety.

## Where it runs
- Filament v4.3+ components (`TextInput`, `Textarea`, `MarkdownEditor`, `RichEditor`) automatically attach `App\Rules\CleanContent` via `AppPanelProvider`, covering resource schemas, pages, and widgets.
- Request validators: contacts (store/update), leads (store/update), API web leads, addresses, and purchase orders all include the clean rule on human-entered strings.
- Livewire purchase order form and address validator now enforce the same rule for nested line items and address fields.

## Using the rule elsewhere
```php
use App\Rules\CleanContent;

return [
    'name' => ['required', 'string', 'max:255', new CleanContent()],
    'bio' => ['nullable', 'string', new CleanContent(['en', 'it'])], // override locales if needed
];
```

The rule skips empty values, checks the configured locales plus fallback, and relies on vendor translations under `resources/lang/vendor/squeaky` for localized error messages. Adjust config before caching.
