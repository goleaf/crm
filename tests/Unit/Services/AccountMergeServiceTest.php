<?php

declare(strict_types=1);

use App\Models\AccountMerge;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use App\Services\AccountMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new AccountMergeService;
});

it('merges accounts with no relationships successfully', function (): void {
    $primary = Company::factory()->create(['name' => 'Primary Corp']);
    $duplicate = Company::factory()->create(['name' => 'Duplicate Corp']);

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue()
        ->and($result['merge_id'])->not->toBeNull()
        ->and($result['error'])->toBeNull();

    $duplicate->refresh();
    expect($duplicate->trashed())->toBeTrue();
});

it('provides complete merge preview with all fields', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Primary Corp',
        'website' => 'https://primary.example.com',
        'revenue' => 1000000,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Duplicate Corp',
        'website' => 'https://duplicate.example.com',
        'revenue' => 500000,
    ]);

    $preview = $this->service->previewMerge($primary, $duplicate);

    expect($preview)->toHaveKeys(['name', 'website', 'revenue', 'industry', 'phone'])
        ->and($preview['name']['primary'])->toBe('Primary Corp')
        ->and($preview['name']['duplicate'])->toBe('Duplicate Corp')
        ->and($preview['website']['primary'])->toBe('https://primary.example.com')
        ->and($preview['website']['duplicate'])->toBe('https://duplicate.example.com');
});

it('includes relationship counts in merge preview', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    People::factory()->count(3)->create(['company_id' => $primary->getKey()]);
    People::factory()->count(2)->create(['company_id' => $duplicate->getKey()]);

    Opportunity::factory()->count(1)->create(['company_id' => $primary->getKey()]);
    Opportunity::factory()->count(4)->create(['company_id' => $duplicate->getKey()]);

    $preview = $this->service->previewMerge($primary, $duplicate);

    expect($preview)->toHaveKeys(['people_count', 'opportunities_count', 'tasks_count', 'notes_count'])
        ->and($preview['people_count']['primary'])->toBe(3)
        ->and($preview['people_count']['duplicate'])->toBe(2)
        ->and($preview['opportunities_count']['primary'])->toBe(1)
        ->and($preview['opportunities_count']['duplicate'])->toBe(4);
});

it('handles merge with empty field values gracefully', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Primary Corp',
        'website' => null,
        'description' => null,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Duplicate Corp',
        'website' => 'https://example.com',
        'description' => 'A great company',
    ]);

    $fieldSelections = [
        'website' => 'duplicate',
        'description' => 'duplicate',
    ];

    $result = $this->service->merge($primary, $duplicate, $fieldSelections);

    expect($result['success'])->toBeTrue();

    $primary->refresh();
    expect($primary->website)->toBe('https://example.com')
        ->and($primary->description)->toBe('A great company');
});

it('prevents merging a company with itself', function (): void {
    $company = Company::factory()->create();

    $result = $this->service->merge($company, $company);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Cannot merge a company with itself');
});

it('prevents merging deleted companies', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    $duplicate->delete();

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Cannot merge deleted companies');
});

it('handles merge errors gracefully and returns error message', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    // Force an error by trying to merge with a trashed company
    $primary->delete();

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->not->toBeNull()
        ->and($result['merge_id'])->toBeNull();
});

it('does not duplicate tasks when merging', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    $task = Task::factory()->create();

    // Attach same task to both companies
    $primary->tasks()->attach($task);
    $duplicate->tasks()->attach($task);

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue();

    $primary->refresh();
    // Should only have the task once
    expect($primary->tasks()->count())->toBe(1);
});

it('does not duplicate notes when merging', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    $note = Note::factory()->create();

    // Attach same note to both companies
    $primary->notes()->attach($note);
    $duplicate->notes()->attach($note);

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue();

    $primary->refresh();
    // Should only have the note once
    expect($primary->notes()->count())->toBe(1);
});

it('records merge operation with authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue();

    $accountMerge = AccountMerge::find($result['merge_id']);
    expect($accountMerge->merged_by_user_id)->toBe($user->getKey());
});

it('records merge operation without authenticated user', function (): void {
    $primary = Company::factory()->create();
    $duplicate = Company::factory()->create();

    $result = $this->service->merge($primary, $duplicate);

    expect($result['success'])->toBeTrue();

    $accountMerge = AccountMerge::find($result['merge_id']);
    expect($accountMerge->merged_by_user_id)->toBeNull();
});

it('rollback returns not implemented error', function (): void {
    $result = $this->service->rollback(999);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('not yet implemented');
});

it('formats enum values in merge preview', function (): void {
    $primary = Company::factory()->create([
        'industry' => \App\Enums\Industry::TECHNOLOGY,
    ]);

    $duplicate = Company::factory()->create([
        'industry' => \App\Enums\Industry::MANUFACTURING,
    ]);

    $preview = $this->service->previewMerge($primary, $duplicate);

    expect($preview['industry']['primary'])->toBe(\App\Enums\Industry::TECHNOLOGY->label())
        ->and($preview['industry']['duplicate'])->toBe(\App\Enums\Industry::MANUFACTURING->label());
});

it('only applies field selections for fillable fields', function (): void {
    $primary = Company::factory()->create(['name' => 'Primary']);
    $duplicate = Company::factory()->create(['name' => 'Duplicate']);

    // Try to set a non-fillable field
    $fieldSelections = [
        'id' => 'duplicate', // id is not fillable
        'name' => 'duplicate',
    ];

    $result = $this->service->merge($primary, $duplicate, $fieldSelections);

    expect($result['success'])->toBeTrue();

    $primary->refresh();
    // Name should be updated
    expect($primary->name)->toBe('Duplicate');
    // ID should remain unchanged
    expect($primary->getKey())->not->toBe($duplicate->getKey());
});

it('preserves empty string values when not selected', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Primary',
        'description' => 'Original description',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Duplicate',
        'description' => '', // empty string
    ]);

    $fieldSelections = [
        'description' => 'duplicate',
    ];

    $result = $this->service->merge($primary, $duplicate, $fieldSelections);

    expect($result['success'])->toBeTrue();

    $primary->refresh();
    // Should keep original since duplicate value is empty
    expect($primary->description)->toBe('Original description');
});
