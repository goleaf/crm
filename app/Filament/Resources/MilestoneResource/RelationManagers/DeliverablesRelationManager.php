<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\RelationManagers;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DeliverablesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliverables';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-check-badge';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('app.labels.name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('app.labels.description'))
                ->rows(3)
                ->columnSpanFull(),
            DatePicker::make('due_date')
                ->label(__('app.labels.due_date'))
                ->required(),
            Textarea::make('acceptance_criteria')
                ->label(__('app.labels.acceptance_criteria'))
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('requires_approval')
                ->label(__('app.labels.requires_approval'))
                ->default(false),
            TextInput::make('completion_evidence_url')
                ->label(__('app.labels.completion_evidence_link'))
                ->url()
                ->maxLength(2048),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label(__('app.labels.name'))->searchable(),
                TextColumn::make('status')->label(__('app.labels.status'))->badge(),
                TextColumn::make('owner.name')->label(__('app.labels.owner'))->toggleable(),
                TextColumn::make('due_date')->label(__('app.labels.due_date'))->date()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('due_date');
    }
}

