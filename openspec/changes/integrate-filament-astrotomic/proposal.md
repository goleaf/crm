# Proposal: integrate-filament-astrotomic

## Change ID
- `integrate-filament-astrotomic`

## Summary
- Capture requirements for integrating `doriiaan/filament-astrotomic` (Filament v4 copy of cactus-galaxy plugin) to manage translatable models using `astrotomic/laravel-translatable`.
- Ensure locales are configured, the plugin is added to panels, resources and pages use the provided translatable traits, and forms leverage translatable tabs.
- Document modal form handling and searchable columns for translations.

## Capabilities
- `translatable-config`: Configure `astrotomic/laravel-translatable` locales and publish config.
- `translatable-plugin`: Register the Filament Astrotomic plugin on panels for Filament v4.
- `translatable-resources-and-pages`: Apply resource/page traits to enable translations across list/create/edit/view pages.
- `translatable-forms`: Use translatable tabs, naming strategies, prepend/append tabs, and main-locale validation.
- `translatable-modals-and-search`: Support translation-safe modal actions and searchable columns via translation-aware queries.

## Notes
- Source: https://filamentphp.com/plugins/doriiaan-astrotomic (repo: `doriiaan/filament-astrotomic`)
