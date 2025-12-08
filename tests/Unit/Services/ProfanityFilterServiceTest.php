<?php

declare(strict_types=1);

use App\Services\Content\ProfanityFilterService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->service = resolve(ProfanityFilterService::class);
});

it('detects profanity in text', function (): void {
    $result = $this->service->hasProfanity('This is fucking awesome');

    expect($result)->toBeTrue();
});

it('returns false for clean text', function (): void {
    $result = $this->service->hasProfanity('This is completely clean text');

    expect($result)->toBeFalse();
});

it('cleans profanity from text', function (): void {
    $cleaned = $this->service->clean('This is fucking awesome');

    expect($cleaned)
        ->toContain('****')
        ->not->toContain('fucking');
});

it('uses custom mask character', function (): void {
    $cleaned = $this->service->clean('This is fucking awesome', maskCharacter: '#');

    expect($cleaned)
        ->toContain('#######')
        ->not->toContain('fucking');
});

it('analyzes text and returns detailed results', function (): void {
    $result = $this->service->analyze('This is fucking shit');

    expect($result)
        ->toHaveKey('has_profanity', true)
        ->toHaveKey('count')
        ->toHaveKey('unique_profanities')
        ->toHaveKey('clean_text')
        ->toHaveKey('original_text');

    expect($result['count'])->toBeGreaterThan(0);
    expect($result['unique_profanities'])->not->toBeEmpty();
});

it('checks text in specific language', function (): void {
    $result = $this->service->hasProfanity('esto es una mierda', 'spanish');

    expect($result)->toBeTrue();
});

it('checks text against all languages', function (): void {
    $result = $this->service->checkAllLanguages('fuck merde scheiße mierda');

    expect($result)
        ->toHaveKey('has_profanity', true)
        ->toHaveKey('count')
        ->and($result['count'])->toBeGreaterThan(0);
});

it('validates and cleans text', function (): void {
    $result = $this->service->validateAndClean('This is fucking awesome', logViolations: false);

    expect($result)
        ->toHaveKey('valid', false)
        ->toHaveKey('clean_text')
        ->toHaveKey('profanities_found');

    expect($result['clean_text'])
        ->not->toContain('fucking');
});

it('batch checks multiple texts', function (): void {
    $texts = [
        'Clean text',
        'This is fucking bad',
        'Another clean one',
        'More shit here',
    ];

    $results = $this->service->batchCheck($texts);

    expect($results)
        ->toHaveCount(4)
        ->and($results[0])->toBeFalse()
        ->and($results[1])->toBeTrue()
        ->and($results[2])->toBeFalse()
        ->and($results[3])->toBeTrue();
});

it('caches profanity check results', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(true);

    $result = $this->service->cachedCheck('This is fucking awesome');

    expect($result)->toBeTrue();
});

it('clears cache for specific text', function (): void {
    Cache::shouldReceive('forget')
        ->once()
        ->andReturn(true);

    $this->service->clearCache('specific text');
});

it('handles empty text gracefully', function (): void {
    expect(fn () => $this->service->hasProfanity(''))
        ->toThrow(Exception::class);
});

it('detects profanity with character substitutions', function (): void {
    // Test with common substitutions
    $result = $this->service->hasProfanity('This is sh1t');

    expect($result)->toBeTrue();
});

it('detects profanity with separators', function (): void {
    $result = $this->service->hasProfanity('This is f-u-c-k awesome');

    expect($result)->toBeTrue();
});

it('respects false positives', function (): void {
    // 'hello' is in false positives list
    $result = $this->service->hasProfanity('hello world');

    expect($result)->toBeFalse();
});

it('returns correct profanity count', function (): void {
    $result = $this->service->analyze('fuck shit damn');

    expect($result['count'])->toBe(3);
});

it('identifies unique profanities', function (): void {
    $result = $this->service->analyze('fuck fuck shit fuck');

    expect($result['unique_profanities'])
        ->not->toBeEmpty();

    // Verify count is less than total words (deduplication works)
    expect(count($result['unique_profanities']))->toBeLessThan(4);
});
