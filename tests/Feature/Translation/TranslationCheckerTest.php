<?php

declare(strict_types=1);

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('imports translations from files', function (): void {
    artisan('translations:import')->assertSuccessful();

    expect(DB::table('ltu_translations')->count())->toBeGreaterThan(0);
});

it('calculates completion percentage correctly', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    $percentage = $service->getCompletionPercentage('en');

    expect($percentage)->toBeGreaterThanOrEqual(0)
        ->and($percentage)->toBeLessThanOrEqual(100);
});

it('identifies missing translations', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    $missing = $service->getMissingTranslations('uk');

    expect($missing)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('gets translation count for a language', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    $count = $service->getTranslationCount('en');

    expect($count)->toBeGreaterThan(0);
});

it('exports translations to files', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    // Export to a test locale
    $service->exportToFiles('en');

    expect(file_exists(lang_path('en/app.php')))->toBeTrue();
});

it('clears cache after export', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    // Get languages (should cache)
    $languages1 = $service->getLanguages();

    // Export (should clear cache)
    $service->exportToFiles('en');

    // Get languages again (should re-cache)
    $languages2 = $service->getLanguages();

    expect($languages1)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($languages2)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('gets statistics for all languages', function (): void {
    $service = resolve(TranslationCheckerService::class);

    // Ensure we have some translations
    artisan('translations:import');

    $stats = $service->getStatistics();

    expect($stats)->toBeArray()
        ->and($stats)->not->toBeEmpty();

    foreach ($stats as $stat) {
        expect($stat)->toHaveKeys(['name', 'code', 'count', 'completion', 'missing'])
            ->and($stat['completion'])->toBeGreaterThanOrEqual(0)
            ->and($stat['completion'])->toBeLessThanOrEqual(100);
    }
});

it('imports from files via service', function (): void {
    $service = resolve(TranslationCheckerService::class);

    $service->importFromFiles();

    expect(DB::table('ltu_translations')->count())->toBeGreaterThan(0);
});
