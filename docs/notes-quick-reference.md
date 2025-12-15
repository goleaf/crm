# Notes System - Quick Reference

## Add Notes to a Model

```php
use App\Models\Concerns\HasNotes;

class YourModel extends Model
{
    use HasNotes;
}
```

## Common Operations

### Create and Attach a Note
```php
$note = Note::create([
    'title' => 'Meeting Notes',
    'category' => NoteCategory::MEETING,
    'visibility' => NoteVisibility::INTERNAL,
    'team_id' => auth()->user()->currentTeam->id,
]);

$model->addNote($note);
```

### Get All Notes
```php
$notes = $model->notes; // Collection
$count = $model->notes()->count(); // Count without loading
```

### Remove a Note
```php
$model->removeNote($note);
```

### Check if Note Exists
```php
if ($model->hasNote($note)) {
    // Note is attached
}
```

### Sync Notes
```php
// With Note instances
$model->syncNotes([$note1, $note2]);

// With IDs
$model->syncNotes([1, 2, 3]);
```

## Eager Loading

```php
// Single model
$model->load('notes');

// Multiple models
$models = Model::with('notes')->get();

// Conditional loading
$models = Model::with(['notes' => function ($query) {
    $query->where('visibility', NoteVisibility::EXTERNAL);
}])->get();
```

## Filament Relation Manager

### Create Relation Manager
```bash
php artisan make:filament-relation-manager YourResource notes title
```

### Register in Resource
```php
public static function getRelations(): array
{
    return [
        RelationManagers\NotesRelationManager::class,
    ];
}
```

### Basic Relation Manager Template
```php
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
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('category')->badge(),
                TextColumn::make('created_at')->dateTime(),
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

## Filtering Notes

```php
// By category
$meetingNotes = $model->notes()
    ->where('category', NoteCategory::MEETING)
    ->get();

// By visibility
$publicNotes = $model->notes()
    ->where('visibility', NoteVisibility::EXTERNAL)
    ->get();

// By date range
$recentNotes = $model->notes()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();
```

## Testing

```php
it('can add notes to model', function () {
    $model = YourModel::factory()->create();
    $note = Note::factory()->create();
    
    $model->addNote($note);
    
    expect($model->notes)->toHaveCount(1);
    expect($model->hasNote($note))->toBeTrue();
});

it('can sync notes', function () {
    $model = YourModel::factory()->create();
    $notes = Note::factory()->count(3)->create();
    
    $model->syncNotes($notes);
    
    expect($model->fresh()->notes)->toHaveCount(3);
});
```

## Performance Tips

```php
// ❌ N+1 Query Problem
foreach ($models as $model) {
    echo $model->notes->count(); // Queries for each model
}

// ✅ Eager Load with Count
$models = Model::withCount('notes')->get();
foreach ($models as $model) {
    echo $model->notes_count; // No additional queries
}

// ✅ Paginate Large Note Collections
$notes = $model->notes()->paginate(25);

// ✅ Cache Note Counts
$count = cache()->remember(
    "model.{$model->id}.notes.count",
    3600,
    fn () => $model->notes()->count()
);
```

## Authorization

```php
// Check before attaching
if (auth()->user()->can('attachNote', $model)) {
    $model->addNote($note);
}

// Check before detaching
if (auth()->user()->can('detachNote', [$model, $note])) {
    $model->removeNote($note);
}

// In Filament Relation Manager
public static function canAttach(): bool
{
    return auth()->user()->can('update', $this->getOwnerRecord());
}
```

## Models with Notes

- Company
- People
- Opportunity
- SupportCase
- Lead
- Task
- Delivery
- Project

## Note Properties

```php
$note->title;              // string
$note->category;           // NoteCategory enum
$note->visibility;         // NoteVisibility enum
$note->is_template;        // boolean
$note->creation_source;    // CreationSource enum
$note->team_id;           // int
$note->creator_id;        // int
$note->body();            // string (from custom field)
$note->plainBody();       // string (stripped HTML)
```

## Visibility Levels

```php
NoteVisibility::PRIVATE;   // Only creator can see
NoteVisibility::INTERNAL;  // Team members can see
NoteVisibility::EXTERNAL;  // Customers/external users can see
```

## Categories

```php
NoteCategory::GENERAL;
NoteCategory::MEETING;
NoteCategory::CALL;
NoteCategory::EMAIL;
// ... and more
```

## Troubleshooting

### Notes Not Showing
1. Check team scoping: `$model->notes()->withoutGlobalScopes()->get()`
2. Verify relationship is loaded: `$model->load('notes')`
3. Check visibility: `$note->visibility`

### Duplicate Notes
Use `syncNotes()` instead of multiple `addNote()` calls.

### Performance Issues
1. Eager load: `Model::with('notes')`
2. Paginate: `$model->notes()->paginate(25)`
3. Cache counts: `withCount('notes')`

## Resources

- Trait: `app/Models/Concerns/HasNotes.php`
- Steering: `.kiro/steering/model-notes.md`
- Tests: `tests/Unit/Models/Concerns/HasNotesTest.php`
- Full Docs: `docs/model-notes-integration.md`

