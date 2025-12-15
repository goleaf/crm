<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\RelationManagers;

use App\Enums\DependencyType;
use App\Models\Milestone;
use App\Models\MilestoneDependency;
use App\Services\Milestones\DependencyService;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DependenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'dependencies';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-arrows-right-left';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('predecessor_id')
                ->label(__('app.labels.predecessor_milestone'))
                ->options(function (RelationManager $livewire): array {
                    /** @var Milestone $milestone */
                    $milestone = $livewire->getOwnerRecord();

                    return Milestone::query()
                        ->where('project_id', $milestone->project_id)
                        ->whereKeyNot($milestone->getKey())
                        ->orderBy('target_date')
                        ->pluck('title', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required(),
            Select::make('dependency_type')
                ->label(__('app.labels.dependency_type'))
                ->options(DependencyType::class)
                ->required(),
            TextInput::make('lag_days')
                ->label(__('app.labels.lag_days'))
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->required(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('predecessor.title')
                    ->label(__('app.labels.predecessor_milestone'))
                    ->searchable(),
                TextColumn::make('dependency_type')
                    ->label(__('app.labels.dependency_type'))
                    ->badge(),
                TextColumn::make('lag_days')
                    ->label(__('app.labels.lag_days'))
                    ->sortable(),
                TextColumn::make('predecessor.status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (RelationManager $livewire, array $data): MilestoneDependency {
                        /** @var Milestone $milestone */
                        $milestone = $livewire->getOwnerRecord();

                        $predecessor = Milestone::query()->findOrFail((int) $data['predecessor_id']);

                        return resolve(DependencyService::class)->createDependency(
                            $predecessor,
                            $milestone,
                            DependencyType::from((string) $data['dependency_type']),
                            (int) $data['lag_days'],
                        );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return Builder<MilestoneDependency>
     */
    protected function getTableQuery(): Builder
    {
        /** @var Milestone $milestone */
        $milestone = $this->getOwnerRecord();

        return MilestoneDependency::query()
            ->where('successor_id', $milestone->getKey())
            ->with('predecessor');
    }
}

