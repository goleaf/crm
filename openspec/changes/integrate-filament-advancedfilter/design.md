# Design Notes

- Target Filament 3.x (current branch beta) for compatibility; align versions accordingly in composer constraints.
- Use debounce tuning per filter to balance responsiveness and query load; prefer higher debounce on heavy text filters.
- Wrapper customization should respect resource layout standards; ensure accessibility isn’t degraded when swapping wrappers.
- Publish translations when localization is required and keep clause labels consistent across languages.
