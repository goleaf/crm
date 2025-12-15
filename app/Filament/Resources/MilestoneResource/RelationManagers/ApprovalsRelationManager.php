<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\RelationManagers;

use App\Enums\MilestoneApprovalStatus;
use App\Models\MilestoneApproval;
use App\Services\Milestones\MilestoneService;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shield-check';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('approver_id')
                ->label(__('app.labels.approver'))
                ->relationship('approver', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Textarea::make('approval_criteria')
                ->label(__('app.labels.approval_criteria'))
                ->rows(2)
                ->columnSpanFull(),
            Select::make('status')
                ->label(__('app.labels.status'))
                ->options(MilestoneApprovalStatus::class)
                ->required(),
            Textarea::make('decision_comment')
                ->label(__('app.labels.comment'))
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('step_order')->label(__('app.labels.step_order'))->sortable(),
                TextColumn::make('approver.name')->label(__('app.labels.approver'))->toggleable(),
                TextColumn::make('status')->label(__('app.labels.status'))->badge(),
                TextColumn::make('requested_at')->label(__('app.labels.requested_at'))->dateTime()->toggleable(),
                TextColumn::make('decided_at')->label(__('app.labels.decided_at'))->dateTime()->toggleable(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->using(function (MilestoneApproval $record, array $data): MilestoneApproval {
                            $record->update($data);

                            if (isset($data['status'])) {
                                resolve(MilestoneService::class)->recordApprovalDecision(
                                    $record,
                                    MilestoneApprovalStatus::from((string) $data['status']),
                                    $data['decision_comment'] ?? null,
                                );
                            }

                            return $record;
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('step_order');
    }
}

