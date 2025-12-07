# Model Notes Integration Guide

## Overview
This document describes the implementation of the notes functionality for Eloquent models, inspired by the Laravel News article on adding notes to models.

## Architecture

### HasNotes Trait
Location: `app/Models/Concerns/HasNotes.php`

The `HasNotes` trait provides a polymorphic many-to-many relationship for attaching notes to any model.

**Key Features:**
- Polymorphic relationship via `noteables` pivot table
- Automatic timestamp tracking
- Notes ordered by creation date (newest first)
- Helper methods for common operations

### Database Structure
- **notes table**: Stores note records
- **noteables pivot table**: Polymorphic many-to-many relationship
  - `note_id`: Foreign key to notes
  - `noteable_id`: ID of the related model
  - `noteable_type`: Class name of the related model
  - `timestamps`: When the note was attached

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

#### `notes()` - Relationship
Returns the morphToMany relationship for accessing notes.

```php
$model->notes; // Collection of Note models
$model->notes()->count(); // Count notes
$model->notes()->where('category', 'meeting')->get(); // Filter notes
```

#### `addNote(Note $note)` - Attach a Note
Attaches a note to the model.

```php
$note = Note::create([
    'title' => 'Meeting Notes',
    'team_id' => auth()->user()->currentTeam->id,
]);

$model->addNote($note);
```

#### `removeNote(Note $note)` - Detach a Note
Removes a note from the model.

```php
$model->removeNote($note);
```

#### `hasNote(Note $note)` - Check if Note Exists
Checks if a specific note is attached to the model.

```php
if ($model->hasNote($note)) {
    // Note is attached
}
```

#### `syncNotes(array $notes)` - Sync Notes
Syncs notes, accepting either Note instances or IDs.

```php
// Using Note instances
$model->syncNotes([$note1, $note2, $note3]);

// Using IDs
$model->syncNotes([1, 2, 3]);
```

## Models with Notes Support

The following models currently support notes:
- ✅ Company
- ✅ People
- ✅ Opportunity
- ✅ SupportCase
- ✅ Lead
- ✅ Task
- ✅ Delivery
- ✅ Project

## Filament Integration

### Using NotesRelationManager

Each resource can include a `NotesRelationManager` to manage notes in the admin panel.

```php
use App\Filament\Resources\YourResource\RelationManagers\NotesRelationManager;

class YourResource extends Resource
{
    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }
}
```

### Creating a NotesRelationManager

```php
namespace App\Filament\Resources\YourResource\RelationManagers;

use App\Filament\Resources\NoteResource\Forms\NoteForm;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    public function form(Schema $schema): Schema
    {
        return NoteForm::get($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title')),
                TextColumn::make('category')
                    ->label(__('app.labels.category'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DetachAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
```

## Best Practices

### 1. Always Use the Trait
Don't manually define the relationship. Use the `HasNotes` trait for consistency.

```php
// ❌ Don't do this
public function notes()
{
    return $this->morphToMany(Note::class, 'noteable');
}

// ✅ Do this
use HasNotes;
```

### 2. Eager Loading
Avoid N+1 queries by eager loading notes when needed.

```php
// Load notes with the model
$companies = Company::with('notes')->get();

// Load notes conditionally
$company->load('notes');
```

### 3. Note Visibility
Respect note visibility settings when displaying notes.

```php
// Filter by visibility
$publicNotes = $model->notes()
    ->where('visibility', NoteVisibility::EXTERNAL)
    ->get();

// Check visibility before displaying
if (!$note->isPrivate() || auth()->user()->can('view', $note)) {
    // Display note
}
```

### 4. Tenant Scoping
Notes are automatically scoped to the current team/tenant.

```php
// Notes are automatically filtered by team_id
$model->notes; // Only returns notes for current team
```

### 5. Timestamps
The pivot table tracks when notes were attached.

```php
// Access pivot timestamps
foreach ($model->notes as $note) {
    echo $note->pivot->created_at; // When note was attached
}
```

## Testing

### Unit Tests
Location: `tests/Unit/Models/Concerns/HasNotesTest.php`

```php
it('can add notes to a model', function () {
    $model = YourModel::factory()->create();
    $note = Note::factory()->create();
    
    $model->addNote($note);
    
    expect($model->notes)->toHaveCount(1);
    expect($model->hasNote($note))->toBeTrue();
});

it('can remove notes from a model', function () {
    $model = YourModel::factory()->create();
    $note = Note::factory()->create();
    
    $model->addNote($note);
    $model->removeNote($note);
    
    expect($model->fresh()->notes)->toHaveCount(0);
});

it('can sync notes', function () {
    $model = YourModel::factory()->create();
    $note1 = Note::factory()->create();
    $note2 = Note::factory()->create();
    
    $model->syncNotes([$note1, $note2]);
    
    expect($model->fresh()->notes)->toHaveCount(2);
});
```

### Feature Tests
Test the full workflow including Filament integration.

```php
it('can attach notes via relation manager', function () {
    $model = YourModel::factory()->create();
    $note = Note::factory()->create();
    
    livewire(NotesRelationManager::class, [
        'ownerRecord' => $model,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $note->id,
        ]);
    
    expect($model->fresh()->notes)->toHaveCount(1);
});
```

## Performance Considerations

### 1. Limit Queries
Only load notes when needed.

```php
// ❌ Always loading notes
$models = Model::with('notes')->get();

// ✅ Load notes only when displaying
$models = Model::all();
if ($needsNotes) {
    $models->load('notes');
}
```

### 2. Pagination
For models with many notes, paginate the results.

```php
$notes = $model->notes()->paginate(25);
```

### 3. Counting
Use `count()` instead of loading all notes.

```php
// ❌ Loads all notes
$count = $model->notes->count();

// ✅ Counts in database
$count = $model->notes()->count();
```

### 4. Caching
Cache note counts for frequently accessed models.

```php
$noteCount = cache()->remember(
    "model.{$model->id}.notes.count",
    3600,
    fn () => $model->notes()->count()
);
```

## Authorization

### Policy Checks
Always check permissions before attaching/detaching notes.

```php
// In your policy
public function attachNote(User $user, YourModel $model): bool
{
    return $user->can('update', $model);
}

public function detachNote(User $user, YourModel $model, Note $note): bool
{
    return $user->can('update', $model) 
        && $user->can('view', $note);
}
```

### Filament Authorization
Configure authorization in the relation manager.

```php
public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
{
    return auth()->user()->can('view', $ownerRecord);
}

public static function canAttach(): bool
{
    return auth()->user()->can('attachNote', $this->getOwnerRecord());
}
```

## Migration Guide

### Adding Notes to an Existing Model

1. Add the trait to your model:
```php
use App\Models\Concerns\HasNotes;

class YourModel extends Model
{
    use HasNotes;
}
```

2. Create a NotesRelationManager (optional):
```bash
php artisan make:filament-relation-manager YourResource notes title
```

3. Register the relation manager in your resource:
```php
public static function getRelations(): array
{
    return [
        RelationManagers\NotesRelationManager::class,
    ];
}
```

4. Update the steering file if needed:
```markdown
## Models with Notes
- YourModel (newly added)
```

## Troubleshooting

### Notes Not Appearing
1. Check team/tenant scoping
2. Verify the relationship is loaded
3. Check note visibility settings

### Duplicate Notes
Use `syncNotes()` instead of repeatedly calling `addNote()`.

### Performance Issues
1. Eager load notes when needed
2. Paginate large note collections
3. Cache note counts
4. Add database indexes on `noteable_type` and `noteable_id`

## References

- Laravel News Article: "Add Notes Functionality to Eloquent Models"
- Steering File: `.kiro/steering/model-notes.md`
- Trait Implementation: `app/Models/Concerns/HasNotes.php`
- Test Suite: `tests/Unit/Models/Concerns/HasNotesTest.php`

