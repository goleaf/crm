<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Override;

final class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_schedule')
                ->label(__('app.actions.view_schedule'))
                ->icon('heroicon-o-calendar')
                ->url(fn (): string => ProjectResource::getUrl('schedule', ['record' => $this->record])),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.project_details'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label(__('app.labels.name')),
                            TextEntry::make('status')
                                ->label(__('app.labels.status'))
                                ->badge(),
                        ]),
                        TextEntry::make('description')
                            ->label(__('app.labels.description'))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('app.labels.schedule'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('start_date')
                                ->label(__('app.labels.start_date'))
                                ->date(),
                            TextEntry::make('end_date')
                                ->label(__('app.labels.end_date'))
                                ->date(),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('actual_start_date')
                                ->label(__('app.labels.actual_start_date'))
                                ->date(),
                            TextEntry::make('actual_end_date')
                                ->label(__('app.labels.actual_end_date'))
                                ->date(),
                        ]),
                    ]),

                Section::make(__('app.labels.budget'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('budget')
                                ->label(__('app.labels.budget'))
                                ->money('USD'),
                            TextEntry::make('actual_cost')
                                ->label(__('app.labels.actual_cost'))
                                ->money('USD'),
                            TextEntry::make('percent_complete')
                                ->label(__('app.labels.progress'))
                                ->formatStateUsing(fn (float $state): string => number_format($state, 0) . '%'),
                        ]),
                    ]),
            ]);
    }
}
