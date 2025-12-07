<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

final class PipelinePerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Pipeline momentum';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $labels = [];
        $leadCounts = [];
        $opportunityCounts = [];

        $rangeStart = Carbon::now()->startOfWeek()->subWeeks(7);

        for ($week = 0; $week <= 7; $week++) {
            $weekStart = (clone $rangeStart)->addWeeks($week);
            $weekEnd = (clone $weekStart)->endOfWeek();

            $labels[] = $weekStart->format('M j');
            $leadCounts[] = Lead::whereBetween('created_at', [$weekStart, $weekEnd])->count();
            $opportunityCounts[] = Opportunity::whereBetween('created_at', [$weekStart, $weekEnd])->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $leadCounts,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'tension' => 0.45,
                    'fill' => true,
                ],
                [
                    'label' => 'Opportunities',
                    'data' => $opportunityCounts,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.45,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
