<?php

declare(strict_types=1);

use App\Console\Commands\ExportAutoTranslationsCommand;
use App\Console\Commands\ImportAutoTranslationJsonCommand;
use Illuminate\Support\Facades\Artisan;

Artisan::addCommands([
    ExportAutoTranslationsCommand::class,
    ImportAutoTranslationJsonCommand::class,
]);

Artisan::command('auto-translations:sync-json {locale}', function (string $locale): void {
    $this->call(ImportAutoTranslationJsonCommand::class, [
        'locale' => $locale,
        '--import' => true,
    ]);
})->describe('Convert lang/{locale}.json back to PHP files and import into the Translation Checker database');
