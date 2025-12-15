<?php

declare(strict_types=1);

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->service = new TranslationCheckerService(cacheTtl: 3600);
});

it('caches language list', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->with('translations.languages', 3600, Mockery::any())
        ->andReturn(collect([
            (object) ['id' => 1, 'code' => 'en', 'name' => 'English'],
        ]));

    $languages = $this->service->getLanguages();

    expect($languages)->toHaveCount(1)
        ->and($languages->first()->code)->toBe('en');
});

it('calculates completion percentage correctly', function (): void {
    DB::shouldReceive('table')
        ->with('ltu_translations')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('count')
        ->andReturn(80, 100); // target count, base count

    Cache::shouldReceive('remember')
        ->andReturn(1, 2); // language IDs

    $percentage = $this->service->getCompletionPercentage('uk');

    expect($percentage)->toBe(80.0);
});

it('returns 100% when base count is zero', function (): void {
    DB::shouldReceive('table')
        ->with('ltu_translations')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('count')
        ->andReturn(0, 0);

    Cache::shouldReceive('remember')
        ->andReturn(1, 2);

    $percentage = $this->service->getCompletionPercentage('uk');

    expect($percentage)->toBe(100.0);
});

it('gets translation count for a language', function (): void {
    DB::shouldReceive('table')
        ->with('ltu_translations')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->with('language_id', 1)
        ->andReturnSelf();
    DB::shouldReceive('count')
        ->andReturn(150);

    Cache::shouldReceive('remember')
        ->andReturn(1);

    $count = $this->service->getTranslationCount('en');

    expect($count)->toBe(150);
});

it('clears cache correctly', function (): void {
    Cache::shouldReceive('forget')->once()->with('translations.languages');
    Cache::shouldReceive('tags')->once()->with(['translations'])->andReturnSelf();
    Cache::shouldReceive('flush')->once();

    $this->service->clearCache();
});

it('gets missing translations', function (): void {
    DB::shouldReceive('table')
        ->with('ltu_translations as base')
        ->andReturnSelf();
    DB::shouldReceive('leftJoin')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('whereNull')
        ->andReturnSelf();
    DB::shouldReceive('select')
        ->andReturnSelf();
    DB::shouldReceive('get')
        ->andReturn(collect([
            (object) ['phrase_id' => 1, 'value' => 'Test'],
        ]));

    Cache::shouldReceive('remember')
        ->andReturn(1, 2);

    $missing = $this->service->getMissingTranslations('uk');

    expect($missing)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($missing)->toHaveCount(1);
});

it('gets statistics for all languages', function (): void {
    Cache::shouldReceive('remember')
        ->with('translations.languages', 3600, Mockery::any())
        ->andReturn(collect([
            (object) ['id' => 1, 'code' => 'en', 'name' => 'English'],
            (object) ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian'],
        ]));

    DB::shouldReceive('table')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('count')
        ->andReturn(100, 100, 80, 100);
    DB::shouldReceive('leftJoin')
        ->andReturnSelf();
    DB::shouldReceive('whereNull')
        ->andReturnSelf();
    DB::shouldReceive('select')
        ->andReturnSelf();
    DB::shouldReceive('get')
        ->andReturn(collect([]), collect([]));

    Cache::shouldReceive('remember')
        ->andReturn(1, 2, 1, 2);

    $stats = $this->service->getStatistics();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys(['en', 'uk'])
        ->and($stats['en'])->toHaveKeys(['name', 'code', 'count', 'completion', 'missing']);
});
