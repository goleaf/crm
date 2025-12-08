<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ActivityFeed extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rss';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.activity-feed';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.activity_feed');
    }

    public function getTitle(): string
    {
        return __('app.pages.activity_feed.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color)
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.activity_types.{$state}")
                    )
                    ->color(fn ($record) => $record->color),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->limit(100)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->description(fn ($record): string => $record->created_at->format('l, F j, Y')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->options([
                        'task' => __('app.activity_types.task'),
                        'note' => __('app.activity_types.note'),
                        'opportunity' => __('app.activity_types.opportunity'),
                        'case' => __('app.activity_types.case'),
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Tables\Filters\Indicators\DatePicker::make('created_from')
                            ->label(__('app.labels.created_from')),
                        Tables\Filters\Indicators\DatePicker::make('created_until')
                            ->label(__('app.labels.created_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 25, 50, 100])
            ->poll('30s')
            ->striped();
    }

    protected function getTableQuery(): Builder
    {
        $teamId = filament()->getTenant()->id;

        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/app/tasks/', id) as url"),
            ])
            ->where('team_id', $teamId);

        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/app/notes/', id) as url"),
            ])
            ->where('team_id', $teamId);

        $opportunities = Opportunity::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'opportunity' as activity_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color"),
                DB::raw("CONCAT('/app/opportunities/', id) as url"),
            ])
            ->where('team_id', $teamId);

        $cases = SupportCase::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'case' as activity_type"),
                DB::raw("'heroicon-o-lifebuoy' as icon"),
                DB::raw("'warning' as color"),
                DB::raw("CONCAT('/app/cases/', id) as url"),
            ])
            ->where('team_id', $teamId);

        return DB::query()
            ->fromSub(
                $tasks->union($notes)->union($opportunities)->union($cases),
                'activities'
            );
    }
}
