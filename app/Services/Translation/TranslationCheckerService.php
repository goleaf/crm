<?php

declare(strict_types=1);

namespace App\Services\Translation;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final readonly class TranslationCheckerService
{
    public function __construct(
        private int $cacheTtl = 3600,
    ) {}

    /**
     * Get all languages
     */
    public function getLanguages(): Collection
    {
        return Cache::remember(
            'translations.languages',
            $this->cacheTtl,
            fn () => DB::table('ltu_languages')->get(),
        );
    }

    /**
     * Get missing translations for a language
     */
    public function getMissingTranslations(string $locale): Collection
    {
        $baseLocale = config('translations.source_language', 'en');

        return DB::table('ltu_translations as base')
            ->leftJoin('ltu_translations as target', function ($join) use ($locale): void {
                $join->on('base.phrase_id', '=', 'target.phrase_id')
                    ->where('target.language_id', '=', $this->getLanguageId($locale));
            })
            ->where('base.language_id', $this->getLanguageId($baseLocale))
            ->whereNull('target.id')
            ->select('base.*')
            ->get();
    }

    /**
     * Get translation completion percentage
     */
    public function getCompletionPercentage(string $locale): float
    {
        $baseLocale = config('translations.source_language', 'en');
        $baseCount = $this->getTranslationCount($baseLocale);
        $targetCount = $this->getTranslationCount($locale);

        if ($baseCount === 0) {
            return 100.0;
        }

        return round(($targetCount / $baseCount) * 100, 2);
    }

    /**
     * Get translation count for a language
     */
    public function getTranslationCount(string $locale): int
    {
        return DB::table('ltu_translations')
            ->where('language_id', $this->getLanguageId($locale))
            ->count();
    }

    /**
     * Export translations to PHP files
     */
    public function exportToFiles(string $locale): void
    {
        $translations = DB::table('ltu_translations as t')
            ->join('ltu_phrases as p', 't.phrase_id', '=', 'p.id')
            ->join('ltu_translation_files as f', 'p.translation_file_id', '=', 'f.id')
            ->where('t.language_id', $this->getLanguageId($locale))
            ->select('f.name as file', 'p.key', 't.value')
            ->get()
            ->groupBy('file');

        foreach ($translations as $file => $items) {
            $path = lang_path("{$locale}/{$file}.php");
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $content = "<?php\n\nreturn [\n";
            foreach ($items as $item) {
                $key = addslashes((string) $item->key);
                $value = addslashes((string) $item->value);
                $content .= "    '{$key}' => '{$value}',\n";
            }
            $content .= "];\n";

            file_put_contents($path, $content);
        }

        $this->clearCache();
    }

    /**
     * Import translations from PHP files
     */
    public function importFromFiles(): void
    {
        // Trigger the standard artisan command for main app files
        Artisan::call('translations:import');

        // Manually import module translations
        $this->importModuleTranslations();

        $this->clearCache();
    }

    /**
     * Import translations from module paths
     */
    private function importModuleTranslations(): void
    {
        $paths = config('translations.module_paths', []);
        $sourceLanguage = config('translations.source_language', 'en');
        $sourceLanguageId = $this->getLanguageId($sourceLanguage);

        foreach ($paths as $pathPattern) {
            // Expand glob pattern to find actual directories
            // e.g. app-modules/*/src/resources/lang
            $dirs = glob(base_path($pathPattern), GLOB_ONLYDIR);

            if (! $dirs) {
                continue;
            }

            foreach ($dirs as $dir) {
                $sourceDir = "{$dir}/{$sourceLanguage}";
                if (! is_dir($sourceDir)) {
                    continue;
                }

                $files = File::allFiles($sourceDir);

                foreach ($files as $file) {
                    $filename = $file->getFilenameWithoutExtension();
                    // We treat module files as if they are in the root namespace for now,
                    // or we could prefix them. For simple overriding, using filename is standard.
                    $this->processImportFile($file->getPathname(), $filename, $sourceLanguageId);
                }
            }
        }
    }

    /**
     * Process a single translation file and upsert into database
     */
    private function processImportFile(string $filePath, string $fileName, int $languageId): void
    {
        $translations = include $filePath;

        if (! is_array($translations)) {
            return;
        }

        // 1. Get or Create Translation File Record
        $fileId = DB::table('ltu_translation_files')
            ->where('name', $fileName)
            ->value('id');

        if (! $fileId) {
            $fileId = DB::table('ltu_translation_files')->insertGetId([
                'name' => $fileName,
                'is_vendor' => false, // Treating app-modules as first-party code
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Flatten translations (dot notation)
        $flattened = Arr::dot($translations);

        foreach ($flattened as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            // 2. Get or Create Phrase
            $phraseId = DB::table('ltu_phrases')
                ->where('translation_file_id', $fileId)
                ->where('key', $key)
                ->value('id');

            if (! $phraseId) {
                $phraseId = DB::table('ltu_phrases')->insertGetId([
                    'uuid' => Str::uuid()->toString(),
                    'translation_file_id' => $fileId,
                    'key' => $key,
                    'group' => $fileName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3. Update or Insert Translation for Source Language
            DB::table('ltu_translations')->updateOrInsert(
                [
                    'language_id' => $languageId,
                    'phrase_id' => $phraseId,
                ],
                [
                    'value' => $value,
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): void
    {
        Cache::forget('translations.languages');
        if (config('translations.cache.driver') === 'redis') {
            Cache::tags(['translations'])->flush();
        } else {
            Cache::forget('translations.stats');
        }
    }

    /**
     * Get language ID by locale code
     */
    private function getLanguageId(string $locale): int
    {
        // Try to find the language ID
        $id = Cache::remember(
            "translations.language_id.{$locale}",
            $this->cacheTtl,
            fn () => DB::table('ltu_languages')
                ->where('code', $locale)
                ->value('id'),
        );

        // If not found, try to find 'en' or the configured source language
        if ($id === null) {
            $default = config('translations.source_language', 'en');
            $id = DB::table('ltu_languages')->where('code', $default)->value('id');
        }

        // Extremely safe fallback if even default is missing (db not seeded?)
        return $id ?? 1;
    }
}
