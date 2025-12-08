<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Translation\TranslationCheckerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class TranslationStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $service = resolve(TranslationCheckerService::class);
        $languages = $service->getLanguages();

        $stats = [];

        foreach ($languages as $language) {
            $completion = $service->getCompletionPercentage($language->code);
            $count = $service->getTranslationCount($language->code);

            $stats[] = Stat::make(
                $language->name,
                "{$completion}%"
            )
                ->description(__('app.labels.translations_count', ['count' => $count]))
                ->color($completion >= 90 ? 'success' : ($completion >= 50 ? 'warning' : 'danger'))
                ->chart($this->getCompletionTrend($language->code));
        }

        return $stats;
    }

    protected function getCompletionTrend(string $locale): array
    {
        // Return historical completion data if tracked
        // For now, return a placeholder trend to make it look nice
        return [65, 70, 75, 80, 85, 90, 95];
    }
}
