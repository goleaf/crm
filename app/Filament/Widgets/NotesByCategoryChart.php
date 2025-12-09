<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NoteCategory;
use App\Models\Note;
use App\Support\Metrics\EasyMetrics;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

final class NotesByCategoryChart extends ApexChartWidget
{
    protected static ?string $chartId = 'notesByCategoryChart';

    protected static ?int $contentHeight = 300;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('app.charts.notes_by_category');
    }

    protected function getSubheading(): ?string
    {
        return __('app.charts.notes_by_category_subheading');
    }

    protected function getOptions(): array
    {
        $data = $this->getChartData();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => self::$contentHeight,
            ],
            'series' => array_values($data),
            'labels' => array_keys($data),
            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontFamily' => 'inherit',
                ],
            ],
            'colors' => $this->getPalette(count($data)),
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                    ],
                ],
            ],
            'stroke' => [
                'width' => 0,
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getChartData(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            600,
            function (): array {
                $result = EasyMetrics::doughnutCounts(
                    $this->baseQuery(),
                    'category',
                    NoteCategory::class,
                );

                $data = [];

                foreach ($result['labels'] as $index => $label) {
                    $data[$label] = $result['data'][$index] ?? 0;
                }

                return $data;
            },
        );
    }

    private function baseQuery(): Builder
    {
        $query = Note::query();
        $model = new Note;
        $tenant = Filament::getTenant();
        $table = $model->getTable();

        if ($tenant && Schema::connection($model->getConnectionName())->hasColumn($table, 'team_id')) {
            $query->where($model->qualifyColumn('team_id'), $tenant->getKey());
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    private function getPalette(int $count): array
    {
        $colors = [
            '#3b82f6',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#ec4899',
            '#6b7280',
        ];

        if ($count <= count($colors)) {
            return array_slice($colors, 0, $count);
        }

        return $colors;
    }

    private function cacheKey(): string
    {
        $tenant = Filament::getTenant()?->getKey() ?? 'public';

        return "apex:notes_category:{$tenant}";
    }
}
