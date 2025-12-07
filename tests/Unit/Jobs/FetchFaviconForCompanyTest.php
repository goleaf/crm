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

test('job can be dispatched', function () {
    Queue::fake();

    $company = Company::factory()->create(['team_id' => $this->team->id]);

    FetchFaviconForCompany::dispatch($company);

    Queue::assertPushed(FetchFaviconForCompany::class);
});

test('job has unique id based on company', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $job = new FetchFaviconForCompany($company);

    expect($job->uniqueId())->toBe((string) $company->id);
});

test('job deletes when company is missing', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $job = new FetchFaviconForCompany($company);

    expect($job->deleteWhenMissingModels)->toBeTrue();
});

test('job implements should be unique', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $job = new FetchFaviconForCompany($company);

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBeUnique::class);
});

test('job implements should queue', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $job = new FetchFaviconForCompany($company);

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('job returns early when no domain name', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    $job = new FetchFaviconForCompany($company);
    $job->handle();

    expect($company->fresh()->getMedia('logo'))->toBeEmpty();
});
