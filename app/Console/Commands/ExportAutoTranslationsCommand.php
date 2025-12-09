<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

final class ExportAutoTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-translations:export-base
                            {--locale= : Locale to export (defaults to AUTO_TRANSLATE_SOURCE_LOCALE)}
                            {--output= : Path for the generated JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flatten PHP translation files into a JSON file for AI translation runs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->option('locale') ?: config('auto-translations.source_locale', 'en');
        $output = $this->option('output') ?: base_path("lang/{$locale}.json");

        $translations = [];

        $translations = $this->mergeTranslations($translations, lang_path($locale));
        $translations = $this->mergeTranslations($translations, resource_path("lang/{$locale}"));

        foreach (config('translations.module_paths', []) as $pathPattern) {
            foreach (glob(base_path($pathPattern . '/' . $locale), GLOB_ONLYDIR) ?: [] as $directory) {
                $translations = $this->mergeTranslations($translations, $directory);
            }
        }

        if (empty($translations)) {
            $this->warn("No translations found for locale [{$locale}].");

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($output));
        File::put(
            $output,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        $resourcesCopy = resource_path("lang/{$locale}.json");
        File::ensureDirectoryExists(dirname($resourcesCopy));
        File::copy($output, $resourcesCopy);

        $storagePath = rtrim((string) config('auto-translations.storage_path'), '/');
        $storageCopy = "{$storagePath}/{$locale}.json";

        if ($storagePath !== '' && $storageCopy !== $output) {
            File::ensureDirectoryExists($storagePath);
            File::copy($output, $storageCopy);
        }

        $this->info('Exported ' . count($translations) . " translations to {$output}");

        return self::SUCCESS;
    }

    /**
     * Merge translations from a given directory into the export payload.
     *
     * @param array<string, string> $bag
     * @return array<string, string>
     */
    private function mergeTranslations(array $bag, string $directory): array
    {
        if (! File::isDirectory($directory)) {
            return $bag;
        }

        foreach (File::allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $data = include $file->getPathname();

            if (! is_array($data)) {
                continue;
            }

            $flattened = Arr::dot($data, $file->getFilenameWithoutExtension() . '.');

            foreach ($flattened as $key => $value) {
                if (is_string($value) && ! array_key_exists($key, $bag)) {
                    $bag[$key] = $value;
                }
            }
        }

        return $bag;
    }
}
