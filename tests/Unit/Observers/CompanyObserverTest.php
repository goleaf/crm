<?php

declare(strict_types=1);

use App\Jobs\FetchFaviconForCompany;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('sets creator and team on creating', function () {
    $this->actingAs($this->user);

    $company = Company::factory()->make([
        'creator_id' => null,
        'team_id' => null,
    ]);

    $company->save();

    expect($company->creator_id)->toBe($this->user->id)
        ->and($company->team_id)->toBe($this->team->id);
});

test('dispatches favicon fetch job on created', function () {
    Queue::fake();

    $this->actingAs($this->user);

    $company = Company::factory()->create([
        'team_id' => $this->team->id,
    ]);

    Queue::assertPushed(FetchFaviconForCompany::class, function ($job) use ($company) {
        return $job->company->id === $company->id;
    });
});

test('ensures account owner on team when saved', function () {
    $this->actingAs($this->user);

    $company = Company::factory()->create([
        'team_id' => $this->team->id,
        'account_owner_id' => $this->user->id,
    ]);

    $company->update(['name' => 'Updated Name']);

    expect($this->team->fresh()->users->contains($this->user))->toBeTrue();
});

test('invalidates ai summary on save', function () {
    $this->actingAs($this->user);

    $company = Company::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $company->aiSummaries()->create([
        'team_id' => $this->team->id,
        'summary' => 'Test summary',
        'model' => 'gpt-4',
    ]);

    expect($company->aiSummaries()->count())->toBe(1);

    $company->update(['name' => 'Updated Name']);

    expect($company->fresh()->aiSummaries()->count())->toBe(0);
});
