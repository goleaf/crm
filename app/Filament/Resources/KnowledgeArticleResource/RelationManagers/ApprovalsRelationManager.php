<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\RelationManagers;

use App\Enums\Knowledge\ApprovalStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shield-check';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('approver_id')
                    ->relationship('approver', 'name')
                    ->label(__('app.labels.approver'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('status')
                    ->label(__('app.labels.status'))
                    ->options(ApprovalStatus::class)
                    ->default(ApprovalStatus::PENDING)
                    ->required(),
                DateTimePicker::make('due_at')
                    ->label(__('app.labels.due_at'))
                    ->seconds(false),
                Textarea::make('decision_notes')
                    ->label(__('app.labels.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                TextColumn::make('approver.name')
                    ->label(__('app.labels.approver'))
                    ->toggleable(),
                TextColumn::make('requestedBy.name')
                    ->label(__('app.labels.requested_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (ApprovalStatus|string|null $state): string => $state instanceof ApprovalStatus ? $state->getColor() : (ApprovalStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ApprovalStatus|string|null $state): string => $state instanceof ApprovalStatus ? $state->getLabel() : (ApprovalStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('due_at')
                    ->label(__('app.labels.due_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('decided_at')
                    ->label(__('app.labels.decided_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decision_notes')
                    ->label(__('app.labels.notes'))
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
