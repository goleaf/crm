<?php

declare(strict_types=1);

namespace App\Services;

use Composer\Autoload\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;

final class SeederGeneratorService
{
    /**
     * @return array<string, string>
     */
    public function modelOptions(): array
    {
        return collect(ClassMapGenerator::createMap(app_path('Models')))
            ->keys()
            ->filter(fn (string $class): bool => $this->isModelClass($class))
            ->mapWithKeys(function (string $class): array {
                $relative = Str::of($class)
                    ->after('App\\')
                    ->ltrim('\\')
                    ->toString();

                return [$relative => $class];
            })
            ->sort()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function tableOptions(): array
    {
        return collect(Schema::getTables())
            ->map(fn (mixed $table): array => (array) $table)
            ->map(fn (array $table): ?string => $table['name']
                ?? $table['Name']
                ?? $table['TABLE_NAME']
                ?? $table['table_name']
                ?? Arr::first($table))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function buildOptions(array $state): array
    {
        $mode = $state['mode'] ?? 'model';
        $options = [
            '--model-mode' => $mode === 'model',
            '--table-mode' => $mode === 'table',
        ];

        if ($mode === 'model' && ! empty($state['models'])) {
            $options['--models'] = $this->implodeList($state['models']);

            if (! ($state['include_relations'] ?? true)) {
                $options['--without-relations'] = true;
            } elseif (! empty($state['relations'])) {
                $options['--relations'] = $this->implodeList($state['relations']);
            }

            if (! empty($state['relations_limit'])) {
                $options['--relations-limit'] = (int) $state['relations_limit'];
            }
        }

        if ($mode === 'table' && ! empty($state['tables'])) {
            $options['--tables'] = $this->implodeList($state['tables']);
        }

        if (! empty($state['ids'])) {
            $options['--ids'] = $this->implodeList($state['ids']);
        }

        if (! empty($state['ignore_ids'])) {
            $options['--ignore-ids'] = $this->implodeList($state['ignore_ids']);
        }

        if (! empty($state['fields'])) {
            $options['--fields'] = $this->implodeList($state['fields']);
        }

        if (! empty($state['ignore_fields'])) {
            $options['--ignore-fields'] = $this->implodeList($state['ignore_fields']);
        }

        if (($state['where']['field'] ?? null) !== null && ($state['where']['value'] ?? null) !== null) {
            $options['--where'] = collect([
                $state['where']['field'],
                $state['where']['operator'] ?? '=',
                $state['where']['value'],
            ])->implode(',');
        }

        if (($state['where_in']['field'] ?? null) !== null && ! empty($state['where_in']['values'])) {
            $options['--where-in'] = $this->implodeList([
                $state['where_in']['field'],
                ...$this->explodeList($state['where_in']['values']),
            ]);
        }

        if (($state['where_not_in']['field'] ?? null) !== null && ! empty($state['where_not_in']['values'])) {
            $options['--where-not-in'] = $this->implodeList([
                $state['where_not_in']['field'],
                ...$this->explodeList($state['where_not_in']['values']),
            ]);
        }

        if (! empty($state['order_by_field'])) {
            $direction = $state['order_direction'] ?? 'asc';
            $options['--order-by'] = "{$state['order_by_field']},{$direction}";
        }

        if (! empty($state['limit'])) {
            $options['--limit'] = (int) $state['limit'];
        }

        if (! empty($state['output'])) {
            $options['--output'] = $state['output'];
        }

        if (! ($state['add_to_database_seeder'] ?? true)) {
            $options['--no-seed'] = true;
        }

        return $options;
    }

    /**
     * @param  array<string, string|int|bool|null>  $options
     */
    public function run(array $options): string
    {
        Artisan::call('seed:generate', $options);

        return trim(Artisan::output());
    }

    /**
     * @param  array<int, mixed>|string  $values
     */
    private function implodeList(array|string $values): string
    {
        return collect($this->explodeList($values))
            ->filter(fn (string $value): bool => $value !== '')
            ->implode(',');
    }

    /**
     * @param  array<int, mixed>|string|null  $values
     * @return array<int, string>
     */
    private function explodeList(array|string|null $values): array
    {
        return collect(is_array($values) ? $values : explode(',', (string) $values))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    private function isModelClass(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        return ! $reflection->isAbstract() && $reflection->isSubclassOf(Model::class);
    }
}
