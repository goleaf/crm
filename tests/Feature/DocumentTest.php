<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments versions and sets current version', function (): void {
    $document = Document::factory()->create();

    $v1 = DocumentVersion::factory()->create([
        'document_id' => $document->id,
        'team_id' => $document->team_id,
        'version' => 1,
    ]);

    $v2 = DocumentVersion::factory()->create([
        'document_id' => $document->id,
        'team_id' => $document->team_id,
        'version' => 2,
    ]);

    $document->refresh();

    expect($document->current_version_id)->toBe($v2->id);
});

it('shares documents with team users', function (): void {
    $document = Document::factory()->create();
    $user = User::factory()->create();

    DocumentShare::factory()->create([
        'document_id' => $document->id,
        'team_id' => $document->team_id,
        'user_id' => $user->id,
        'permission' => 'edit',
    ]);

    $document->load('shares');

    expect($document->shares)->toHaveCount(1)
        ->and($document->shares->first()->permission)->toBe('edit');
});
