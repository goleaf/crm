# Tailwind CSS 3.4 feature integration

## Release features brought in
- New viewport sizing utilities (`dvh`/`lvh`/`svh`) and `min-h-dvh` for mobile-safe full-height layouts.
- Text wrapping helpers (`text-balance`, `text-pretty`) for nicer headings and helper copy.
- `size-*` utilities to size width/height together (works with arbitrary values).
- `has-*` variant for `:has()`-driven parent styling, plus forced-colors utilities for high-contrast support.
- Additional defaults (expanded opacity scale, larger `grid-rows-*`, subgrid support) available across components.

## Implementation in Filament v4.3+
- `resources/css/filament/app/theme.css` now uses:
  - `min-h-dvh` alongside `min-h-screen` on `.fi-app-layout` with `forced-color-adjust-auto` to respect system forced-color modes.
  - `text-pretty` on the main wrapper and `text-balance` on header headings for balanced titles in dashboards/pages.
  - `size-[calc(var(--spacing)*5)]` on sidebar icons/borders instead of manual width/height to adopt the `size-*` utility.
  - `has-[.fi-sidebar-item.fi-active]:...` on sidebar groups to highlight clusters containing the active item.
- These utilities compile through the existing Tailwind 4 pipeline (superset of 3.4) without extra config; sources remain under `resources/css/filament/*`.

## Usage guidance (new components)
- Prefer `min-h-dvh` (with `min-h-screen` fallback) for any full-height Filament shell or modal to handle mobile chrome.
- Apply `text-balance`/`text-pretty` to panel/page headings and helper copy instead of manual letter-spacing tweaks.
- Use `size-*` (or arbitrary `size-[...]`) for icons, avatars, and badges to avoid duplicating width/height declarations.
- Use `has-*` variants to style parent navigation groups based on active children; avoid brittle descendant selectors.
- When supporting high-contrast/forced-color contexts, lean on `forced-color-adjust-auto|none` utilities rather than custom overrides.

## Spec alignment
- Supports `.kiro/specs/ui-ux/requirements.md` responsiveness/navigation feedback goals by using dvh sizing, balanced headings, and parent-aware nav states in the Filament shell.

## References
- Release notes: https://github.com/tailwindlabs/tailwindcss/releases/tag/v3.4.0
- Theming guidance: `.kiro/steering/filament-theming-branding.md`
- Agent rules: `AGENTS.md` (Tailwind 3.4+ utility expectations)
