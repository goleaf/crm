<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Console\Command;

final class ImportModuleTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:import-modules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from app-modules into the database';

    /**
     * Execute the console command.
     */
    public function handle(TranslationCheckerService $service): int
    {
        $this->info('Starting module translation import...');

        try {
            $service->importFromFiles();
            $this->info('Translations imported successfully (including modules).');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to import translations: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
