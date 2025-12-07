<?php

declare(strict_types=1);

use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFieldType;
use App\Enums\NoteHistoryEvent;
use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\Team;
use App\Services\Notes\NoteHistoryService;
use Relaticle\CustomFields\Services\TenantContextService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

test('note uses media library and casts visibility', function (): void {
    $note = Note::factory()->create();

    expect(class_implements($note))->toContain(HasMedia::class)
        ->and(class_uses_recursive($note))->toContain(InteractsWithMedia::class)
        ->and($note->visibility)->toBeInstanceOf(NoteVisibility::class)
        ->and($note->visibility)->toBe(NoteVisibility::INTERNAL);
});

test('note returns body content and records history snapshot', function (): void {
    $team = Team::factory()->create();
    $field = createCustomFieldFor(
        Note::class,
        NoteField::BODY->value,
        CustomFieldType::RICH_EDITOR->value,
        [],
        $team
    );

    $note = Note::factory()
        ->for($team, 'team')
        ->create([
            'title' => 'Snapshot note',
        ]);

    $note->saveCustomFieldValue($field, '<p>Hello world</p>');
    TenantContextService::setTenantId($team->getKey());

    expect($note->body())->toBe('<p>Hello world</p>')
        ->and($note->plainBody())->toBe('Hello world');

    /** @var NoteHistoryService $historyService */
    $historyService = app(NoteHistoryService::class);
    $historyService->record($note, NoteHistoryEvent::CREATED);

    $history = $note->histories()->first();

    expect($history)->not()->toBeNull()
        ->and($history?->body)->toBe('Hello world')
        ->and($history?->event)->toBe(NoteHistoryEvent::CREATED);
});
