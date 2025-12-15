# Userstamps integration

We use [wildside/userstamps](https://github.com/mattiverse/Laravel-Userstamps) to keep `creator_id`, `editor_id`, and (for soft-deleted models) `deleted_by` in sync with the authenticated user.

## Usage

- Add `use App\Models\Concerns\HasCreator;` to models that need auditing. The trait wraps Laravel Userstamps and continues to expose the `createdBy` accessor that respects `creation_source`.
- Migrations should include `creator_id`, `editor_id`, and `deleted_by` (if the model uses `SoftDeletes`) as nullable foreign keys to `users`.
- For bulk writes that bypass Eloquent events, call `updateWithUserstamps`/`deleteWithUserstamps` so `editor_id`/`deleted_by` are populated.
- To temporarily skip stamping for a specific write, call `$model->withoutUserstamps(fn () => $model->forceFill([...])->save());`.
- If you need a non-default user resolution (e.g., custom guard), configure `Userstamps::resolveUsing(fn () => auth('web')->id());` in a service provider.
