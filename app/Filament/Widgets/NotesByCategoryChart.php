<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NoteCategory;
use App\Models\Note;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class NotesByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Notes by Category';

    protected ?string $description = 'Distribution of notes across categories.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $categoryCounts = Note::query()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item): array {
                $category = NoteCategory::tryFrom($item->category);
                $label = $category?->label() ?? 'General';

                return [$label => $item->count];
            })
            ->toArray();

        // Ensure all categories are represented
        $allCategories = collect(NoteCategory::cases())
            ->mapWithKeys(fn (NoteCategory $category): array => [$category->label() => 0])
            ->toArray();

        $data = array_merge($allCategories, $categoryCounts);

        return [
            'datasets' => [
                [
                    'label' => __('app.labels.notes'),
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',  // blue
                        'rgba(16, 185, 129, 0.5)',  // green
                        'rgba(245, 158, 11, 0.5)',  // amber
                        'rgba(239, 68, 68, 0.5)',   // red
                        'rgba(139, 92, 246, 0.5)',  // purple
                        'rgba(236, 72, 153, 0.5)',  // pink
                        'rgba(107, 114, 128, 0.5)', // gray
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
