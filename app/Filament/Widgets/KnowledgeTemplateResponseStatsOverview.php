<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeTemplateResponse;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class KnowledgeTemplateResponseStatsOverview extends StatsOverviewWidget
{
    public function getHeading(): string
    {
        return __('app.labels.template_responses');
    }

    public function getDescription(): string
    {
        return __('app.messages.template_responses_overview');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $weekStart = \Illuminate\Support\Facades\Date::now()->startOfWeek();

        $totalTemplates = KnowledgeTemplateResponse::count();
        $activeTemplates = KnowledgeTemplateResponse::where('is_active', true)->count();
        $newThisWeek = KnowledgeTemplateResponse::where('created_at', '>=', $weekStart)->count();
        $publicTemplates = KnowledgeTemplateResponse::where('visibility', ArticleVisibility::PUBLIC)->count();

        return [
            Stat::make(__('app.labels.total_templates'), $totalTemplates)
                ->description(__('app.messages.all_template_responses'))
                ->icon('heroicon-o-sparkles')
                ->color('primary'),
            Stat::make(__('app.labels.active_templates'), $activeTemplates)
                ->description(__('app.messages.ready_to_use'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('app.labels.new_this_week'), $newThisWeek)
                ->description(__('app.messages.created_recently'))
                ->icon('heroicon-o-plus-circle')
                ->color('info'),
            Stat::make(__('app.labels.public_templates'), $publicTemplates)
                ->description(__('app.messages.visible_to_customers'))
                ->icon('heroicon-o-globe-alt')
                ->color('warning'),
        ];
    }
}
