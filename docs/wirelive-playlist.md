# Wire:Live 2025 Playlist Widget

## Overview
- Widget surfaces the Wire:Live 2025 Livewire/Filament talks (YouTube playlist) inside the Filament dashboard.
- Lives at `app/Filament/Widgets/WireLivePlaylist.php` with view `resources/views/filament/widgets/wire-live-playlist.blade.php`.
- Added to the main dashboard via `app/Filament/Pages/Dashboard.php`.

## Data source
- Uses the public YouTube playlist: `https://www.youtube.com/playlist?list=PLH3DZfpF7H73EXPI_AhwUBud22VufndZV`.
- Talk titles/speakers are defined in `getPlaylist()`; swap `url` values for per-video links if desired.
- Static data is intentional to avoid runtime HTTP requests in the panel.

## Translations
- Keys live under `app.wirelive.*` in `lang/en/app.php`:
  - `heading`, `subheading`, `watch_playlist`, `watch_talk`, `livewire_focus`.

## UX notes
- Full-width widget with responsive 1/2/3 column cards.
- External links open in a new tab; includes CTA to the full playlist plus per-talk links.
