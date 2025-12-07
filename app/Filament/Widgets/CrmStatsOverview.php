<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

final class CrmStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard overview';

    protected ?string $description = 'High-level activity across your workspace.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $weekStart = Carbon::now()->startOfWeek();

        $openLeads = Lead::whereIn('status', [
            LeadStatus::NEW,
            LeadStatus::WORKING,
            LeadStatus::NURTURING,
        ])->count();

        $newLeadsThisWeek = Lead::where('created_at', '>=', $weekStart)->count();
        $opportunities = Opportunity::count();
        $tasks = Task::count();
        $notesThisWeek = Note::where('created_at', '>=', $weekStart)->count();

        return [
            Stat::make('Open leads', $openLeads)
                ->description("{$newLeadsThisWeek} new this week")
                ->icon('heroicon-o-user-plus')
                ->color('primary'),
            Stat::make('Opportunities', $opportunities)
                ->description('Active pipeline')
                ->icon('heroicon-o-trophy')
                ->color('warning'),
            Stat::make('Tasks', $tasks)
                ->description('Team workload')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success'),
            Stat::make('Notes this week', $notesThisWeek)
                ->description('Recent updates')
                ->icon('heroicon-o-document-text')
                ->color('info'),
        ];
    }
}
