<?php

declare(strict_types=1);

use App\Models\PdfTemplate;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates pdf template', function (): void {
    $team = Team::factory()->create();
    $template = PdfTemplate::factory()->for($team)->create();

    expect($template)->toBeInstanceOf(PdfTemplate::class)
        ->and($template->team_id)->toBe($team->id);
});