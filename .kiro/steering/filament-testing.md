---
inclusion_mode: "conditional"
file_patterns:
  - "tests/**"
  - "app/Filament/**"
---

# Filament Testing (Pest/Livewire)

## Resource pages
- Use `livewire(ListX::class)->assertCanSeeTableRecords(...)` for list coverage.
- `fillForm()->call('create'|'save')->assertHasNoFormErrors()` for create/edit.
- Assert redirects/notifications for actions; cover validation failures.

## Tables
- `searchTable`, `filterTable`, `sortTable`, `assertActionRequiresConfirmation`, `callTableAction`.
- Reorderable tables: `reorderTableRecords`.
- Bulk actions: `callTableBulkAction` and assert side effects.

## Authorization
- Test can/cannot for view/create/edit/delete and navigation visibility.
- Ensure unauthorized users get 403 and do not see actions.

## Widgets
- Assert counts/labels and tenant scoping; avoid hardcoding IDs.

## Factories & data
- Use factories with relationships; prefer RefreshDatabase or DatabaseTransactions.
- Seed minimal data; avoid heavy fixtures.

## Snapshots (optional)
- Snapshot navigation/global search results when structure is stable; update intentionally.
