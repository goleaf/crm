<?php

declare(strict_types=1);

use App\Enums\Industry;
use App\Models\Company;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new DuplicateDetectionService;
});

it('scores identical companies very highly', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Acme Corporation',
        'website' => 'https://acme.example.com',
        'industry' => Industry::MANUFACTURING,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Acme Corporation',
        'website' => 'https://www.acme.example.com',
        'industry' => Industry::MANUFACTURING,
    ]);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThan(90);
});

it('finds duplicates above a threshold', function (): void {
    $company = Company::factory()->create([
        'name' => 'Northern Lights',
        'website' => 'https://northern.example.com',
        'industry' => Industry::RENEWABLE_ENERGY,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Northern Lights LLC',
        'website' => 'https://northern.example.com',
        'industry' => Industry::RENEWABLE_ENERGY,
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

it('provides a merge suggestion that favors primary values', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Horizon Partners',
        'website' => 'https://horizon.example.com',
        'industry' => Industry::CONSULTING,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Horizon Partners Ltd.',
        'website' => '',
        'industry' => Industry::CONSULTING,
    ]);

    $suggestions = $this->service->suggestMerge($primary, $duplicate);

    expect($suggestions->firstWhere('attribute', 'name')['selected'])->toBe('Horizon Partners')
        ->and($suggestions->firstWhere('attribute', 'industry')['selected'])->toBe(Industry::CONSULTING->label());
});

// Edge case tests
it('handles empty company names gracefully', function (): void {
    $primary = Company::factory()->create(['name' => '']);
    $duplicate = Company::factory()->create(['name' => '']);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles null website values', function (): void {
    $primary = Company::factory()->create(['website' => null]);
    $duplicate = Company::factory()->create(['website' => null]);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles special characters in company names', function (): void {
    $primary = Company::factory()->create(['name' => 'Acme & Co., Inc.']);
    $duplicate = Company::factory()->create(['name' => 'ACME and Company Incorporated']);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThan(20);
});

it('handles very long company names', function (): void {
    $longName = str_repeat('A', 255);
    $primary = Company::factory()->create(['name' => $longName]);
    $duplicate = Company::factory()->create(['name' => $longName . ' Inc']);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100)
        ->and($score)->toBeGreaterThan(50);
});

it('respects threshold parameter in findDuplicates', function (): void {
    $company = Company::factory()->create([
        'name' => 'Test Company',
        'website' => 'https://test.example.com',
    ]);

    $highSimilarity = Company::factory()->create([
        'name' => 'Test Company Inc',
        'website' => 'https://test.example.com',
    ]);

    $lowSimilarity = Company::factory()->create([
        'name' => 'Different Corp',
        'website' => 'https://different.example.com',
    ]);

    $highThreshold = $this->service->findDuplicates($company, threshold: 80.0);
    $lowThreshold = $this->service->findDuplicates($company, threshold: 10.0);

    expect($highThreshold->count())->toBeLessThanOrEqual($lowThreshold->count());
});

it('respects limit parameter in findDuplicates', function (): void {
    $company = Company::factory()->create(['name' => 'Base Company']);

    // Create 10 similar companies
    for ($i = 0; $i < 10; $i++) {
        Company::factory()->create([
            'name' => "Base Company {$i}",
            'website' => $company->website,
        ]);
    }

    $limited = $this->service->findDuplicates($company, threshold: 10.0, limit: 3);

    expect($limited->count())->toBeLessThanOrEqual(3);
});

it('handles identical company IDs correctly', function (): void {
    $company = Company::factory()->create();

    $score = $this->service->calculateSimilarity($company, $company);

    expect($score)->toBe(100.0);
});

it('normalizes website domains correctly', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Test Corp',
        'website' => 'https://www.example.com/path',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Test Corp',
        'website' => 'http://example.com',
    ]);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(90);
});

it('handles subdomain variations', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Test Corp',
        'website' => 'https://app.example.com',
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Test Corp',
        'website' => 'https://www.example.com',
    ]);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThan(70);
});

it('prefers non-empty values in merge suggestions', function (): void {
    $primary = Company::factory()->create([
        'name' => 'Primary Corp',
        'website' => '',
        'description' => null,
    ]);

    $duplicate = Company::factory()->create([
        'name' => 'Duplicate Corp',
        'website' => 'https://example.com',
        'description' => 'A great company',
    ]);

    $suggestions = $this->service->suggestMerge($primary, $duplicate);

    expect($suggestions->firstWhere('attribute', 'website')['selected'])->toBe('https://example.com')
        ->and($suggestions->firstWhere('attribute', 'description')['selected'])->toBe('A great company');
});

it('handles boundary similarity scores', function (): void {
    $primary = Company::factory()->create(['name' => 'A']);
    $duplicate = Company::factory()->create(['name' => 'Z']);

    $score = $this->service->calculateSimilarity($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0.0)
        ->and($score)->toBeLessThanOrEqual(100.0);
});
