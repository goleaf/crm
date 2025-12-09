<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Note;
use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->limit(40)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.activity_types.{$state}"),
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->url)
                    ->size(Tables\Actions\ActionSize::Small),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }

    protected function getTableQuery(): Builder
    {
        $teamId = filament()->getTenant()->id;

        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/app/tasks/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(50);

        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/app/notes/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(50);

        return DB::query()
            ->fromSub(
                $tasks->union($notes),
                'activities',
            );
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_activity_feed');
    }
}
