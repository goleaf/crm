# Reactions integration

## Package setup
- Added `binafy/laravel-reactions` with config at `config/laravel-reaction.php` (uses the `users` table, `user_id` foreign key, `web` guard).
- Vendor migrations are auto-loaded; run `php artisan migrate` after updating dependencies to create the `reactions` table.
- Reaction types are translated under `app.reactions.types.*` with labels and actions in `app.php`.

## Model wiring
- Users now use `App\Models\Concerns\InteractsWithReactions`, which:
  - Normalizes reaction storage (one row per user/reactable), captures the request IP (falls back to `cli`), and exposes `reactionsGiven()`.
  - Provides `reaction()`, `removeReaction()`, and `removeReactions()` used by Reactable models.
- Reactable models: `Note`, `KnowledgeArticle`, `KnowledgeArticleComment`, and `TaskComment` implement `HasReaction` and use `Reactable`.
- When adding reactions to another model:
  1) Implement `HasReaction` and `use Reactable;`
  2) Prefetch `reactions_count` via `withCount('reactions')`
  3) If you show “Reacted by me”, add `withExists(['reactions as reacted_by_me' => fn ($q) => $q->where(config('laravel-reactions.user.foreign_key'), auth()->id())])`.

## UI touchpoints (Filament)
- **Knowledge articles**: list view shows reactions count and a “my reaction” toggle; record actions include React/Remove Reaction.
- **Article comments**: relation manager shows counts, per-user reaction flag, and React/Remove actions.
- **Notes**: table now surfaces reactions count + per-user flag with React/Remove actions.
- All UI actions share `App\Support\Reactions\ReactionOptions` for consistent type labels/default.

## Testing
- `tests/Feature/Reactions/ReactionsTest.php` covers creating, updating, and removing reactions plus count helpers on notes.
