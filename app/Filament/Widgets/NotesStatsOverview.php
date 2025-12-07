<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NoteVisibility;
use App\Models\Note;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

final class NotesStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Notes Overview';

    protected ?string $description = 'Summary of notes activity and distribution.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $totalNotes = Note::count();
        $notesThisWeek = Note::where('created_at', '>=', $weekStart)->count();
        $notesThisMonth = Note::where('created_at', '>=', $monthStart)->count();

        $templates = Note::where('is_template', true)->count();

        $externalNotes = Note::where('visibility', NoteVisibility::EXTERNAL)->count();

        $notesWithAttachments = Note::has('attachments')->count();

        return [
            Stat::make(__('app.labels.total').' '.__('app.labels.notes'), $totalNotes)
                ->description("{$notesThisWeek} new this week")
                ->icon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make(__('app.labels.notes').' this month', $notesThisMonth)
                ->description('Monthly activity')
                ->icon('heroicon-o-calendar')
                ->color('success'),

            Stat::make(__('app.labels.template').' '.__('app.labels.notes'), $templates)
                ->description('Reusable templates')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning'),

            Stat::make('External '.__('app.labels.notes'), $externalNotes)
                ->description('Customer-visible notes')
                ->icon('heroicon-o-eye')
                ->color('info'),

            Stat::make(__('app.labels.notes').' with '.__('app.labels.attachments'), $notesWithAttachments)
                ->description('Notes containing files')
                ->icon('heroicon-o-paper-clip')
                ->color('gray'),
        ];
    }
}
