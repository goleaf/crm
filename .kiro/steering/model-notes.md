---
inclusion: always
---

# Model Notes System

## Overview
All models that need notes functionality should use the `HasNotes` trait. This provides a consistent, polymorphic many-to-many relationship for attaching notes to any entity.

### Lightweight text notes (Notable package)
- Use `App\Models\Concerns\HasNotableEntries` when you need quick, plain-text notes powered by `eg-mohamed/notable` (`NotableEntry` extends the vendor model so you still get the package scopes). For models that already have `HasNotes`, pull in `HasNotesAndNotables` to expose both APIs without collisions.
- Call `addNotableNote()` / `notableNotes()` / `notableNotesByCreator()` instead of the vendor `addNote()` to avoid clashing with our existing `HasNotes` trait.
- The `team_id` column is required—`addNotableNote()` injects the owning model’s team and `HasTeam` on `NotableEntry` backfills from the current team; don’t bypass the trait or you’ll hit integrity errors.
- Keep rich notes (attachments, categories, visibility) on the existing `Note` model; use Notable entries for lightweight audit-style text.

## Implementation

### Adding Notes to a Model
```php
use App\Models\Concerns\HasNotes;

class YourModel extends Model
{
    use HasNotes;
}
```

### Available Methods
- `notes()` - Relationship to get all notes
- `addNote(Note $note)` - Attach a note
- `removeNote(Note $note)` - Detach a note
- `hasNote(Note $note)` - Check if note is attached
- `syncNotes(array $notes)` - Sync notes (accepts Note instances or IDs)

### Usage Examples
```php
// Get all notes
$model->notes;

// Add a note
$note = Note::create([...]);
$model->addNote($note);

// Remove a note
$model->removeNote($note);

// Check if has note
if ($model->hasNote($note)) {
    // ...
}

// Sync notes
$model->syncNotes([$note1, $note2]);
```

## Models with Notes
The following models currently support notes:
- Company
- People
- Opportunity
- SupportCase
- Lead
- Task
- Delivery
- Project (add when implementing)

## Filament Integration

### Relation Manager
Use `NotesRelationManager` for managing notes in Filament resources:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\NotesRelationManager::class,
    ];
}
```

### Notes Widget
Display notes count in resource widgets or info lists.

## Best Practices
- Always use the trait instead of manually defining relationships
- Notes are ordered by creation date (newest first) by default
- Notes support soft deletes, visibility controls, and categories
- Use `withTimestamps()` to track when notes were attached
- Leverage eager loading: `$model->load('notes')` to avoid N+1 queries

## Authorization
- Check note visibility before displaying (private/internal/external)
- Respect team/tenant boundaries when querying notes
- Use policies to control who can attach/detach notes

## Testing
```php
it('can add notes to model', function () {
    $model = YourModel::factory()->create();
    $note = Note::factory()->create();
    
    $model->addNote($note);
    
    expect($model->notes)->toHaveCount(1);
    expect($model->hasNote($note))->toBeTrue();
});
```
