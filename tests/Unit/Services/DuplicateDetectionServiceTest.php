<?php

declare(strict_types=1);

use App\Models\Company;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DuplicateDetectionService();
});

it('scores identical companies very highly', function () {
    $primary = Company::factory()->create([
        'name' => 'Acme Corporation',
        'website' => 'https://acme.example.com',
        'industry' => 'Manufacturing',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Acme Corporation',
        'website' => 'https://www.acme.example.com',
        'industry' => 'Manufacturing',
    ]);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThan(90);
});

it('finds duplicates above a threshold', function () {
    $company = Company::factory()->create([
        'name' => 'Northern Lights',
        'website' => 'https://northern.example.com',
        'industry' => 'Renewable Energy',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Northern Lights LLC',
        'website' => 'https://northern.example.com',
        'industry' => 'Renewable Energy',
    ]);

    Company::factory()->create([
        'name' => 'Other Corp',
        'website' => 'https://other.example.com',
    ]);

    $duplicates = $this->service->findDuplicates($company, threshold: 40.0);

    expect($duplicates->isNotEmpty())->toBeTrue()
        ->and($duplicates->first()['company']->getKey())->toBe($duplicate->getKey())
        ->and($duplicates->first()['score'])->toBeGreaterThan(40);
});

it('provides a merge suggestion that favors primary values', function () {
    $primary = Company::factory()->create([
        'name' => 'Horizon Partners',
        'website' => 'https://horizon.example.com',
        'industry' => 'Consulting',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Horizon Partners Ltd.',
        'website' => '',
        'industry' => 'consulting',
    ]);

    $suggestions = $this->service->suggestMerge($primary, $duplicate);

    expect($suggestions->firstWhere('attribute', 'name')['selected'])->toBe('Horizon Partners')
        ->and($suggestions->firstWhere('attribute', 'industry')['selected'])->toBe('Consulting');
});
