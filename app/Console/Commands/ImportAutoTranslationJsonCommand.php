<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

final class ImportAutoTranslationJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-translations:json-to-php
                            {locale : Locale code for the JSON file (e.g. es)}
                            {--source= : Optional source JSON path}
                            {--target= : Optional target directory for PHP files}
                            {--import : Run translations:import after writing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert lang/{locale}.json files into structured PHP translation files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->argument('locale');

        $defaultSource = base_path("lang/{$locale}.json");
        $storageCopy = rtrim((string) config('auto-translations.storage_path'), '/');
        $storageFallback = $storageCopy !== '' ? "{$storageCopy}/{$locale}.json" : null;

        $source = $this->option('source') ?: ($this->pickSource($defaultSource, $storageFallback));

        if ($source === null || ! File::exists($source)) {
            $this->error("Source JSON not found for locale [{$locale}]. Checked {$defaultSource}" . ($storageFallback ? " and {$storageFallback}" : '') . '.');

            return self::FAILURE;
        }

        try {
            /** @var array<string, string> $translations */
            $translations = json_decode(File::get($source), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error("Invalid JSON in {$source}.");

            return self::FAILURE;
        }

        $targetBase = $this->option('target') ?: lang_path($locale);
        File::ensureDirectoryExists($targetBase);

        $files = $this->groupByFile($translations);
        $written = 0;

        foreach ($files as $file => $data) {
            $path = rtrim($targetBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "{$file}.php";
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $this->buildPhpFile($data));
            $written++;
        }

        $this->info("Wrote {$written} PHP translation file(s) to {$targetBase}");

        if ($this->option('import')) {
            $this->call('translations:import', ['--language' => $locale]);
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, string> $translations
     *
     * @return array<string, array<string, mixed>>
     */
    private function groupByFile(array $translations): array
    {
        $grouped = [];

        foreach ($translations as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $segments = explode('.', (string) $key);
            $file = array_shift($segments);
            if ($file === null) {
                continue;
            }
            if ($file === '') {
                continue;
            }
            if ($segments === []) {
                continue;
            }

            $nestedKey = implode('.', $segments);
            data_set($grouped[$file], $nestedKey, $value);
        }

        return $grouped;
    }

    /**
     * Convert an array into a PHP translation file.
     */
    private function buildPhpFile(array $data): string
    {
        $body = $this->formatArray($data);

        return "<?php\n\nreturn [\n{$body}\n];\n";
    }

    /**
     * @param array<string, mixed> $data
     */
    private function formatArray(array $data, int $indent = 1): string
    {
        $indentation = str_repeat('    ', $indent);
        $lines = [];

        foreach ($data as $key => $value) {
            $keyString = is_int($key) ? $key : "'" . addslashes($key) . "'";

            if (is_array($value)) {
                $nested = $this->formatArray($value, $indent + 1);
                $lines[] = "{$indentation}{$keyString} => [\n{$nested}\n{$indentation}],";
                continue;
            }

            $valueString = "'" . addslashes((string) $value) . "'";
            $lines[] = "{$indentation}{$keyString} => {$valueString},";
        }

        return implode("\n", $lines);
    }

    private function pickSource(string $defaultSource, ?string $storageFallback): ?string
    {
        if (File::exists($defaultSource)) {
            return $defaultSource;
        }

        if ($storageFallback && File::exists($storageFallback)) {
            return $storageFallback;
        }

        return null;
    }
}
