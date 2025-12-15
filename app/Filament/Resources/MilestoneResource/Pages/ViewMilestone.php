<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\Pages;

use App\Enums\MilestoneApprovalStatus;
use App\Filament\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Services\Milestones\MilestoneService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

final class ViewMilestone extends ViewRecord
{
    protected static string $resource = MilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_progress')
                ->label(__('app.actions.refresh_progress'))
                ->icon('heroicon-o-arrow-path')
                ->action(function (Milestone $record): void {
                    resolve(MilestoneService::class)->updateProgress($record);

                    Notification::make()
                        ->title(__('app.notifications.progress_refreshed'))
                        ->success()
                        ->send();
                }),
            Action::make('submit_for_approval')
                ->label(__('app.actions.submit_for_approval'))
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (Milestone $record): bool => $record->requires_approval && $record->status !== \App\Enums\MilestoneStatus::COMPLETED)
                ->modalWidth(Width::ExtraLarge)
                ->form([
                    Repeater::make('steps')
                        ->label(__('app.labels.approval_steps'))
                        ->schema([
                            Select::make('approver_id')
                                ->label(__('app.labels.approver'))
                                ->relationship('approver', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Textarea::make('approval_criteria')
                                ->label(__('app.labels.approval_criteria'))
                                ->rows(2),
                        ])
                        ->minItems(1)
                        ->required(),
                ])
                ->action(function (Milestone $record, array $data): void {
                    $steps = collect($data['steps'] ?? [])
                        ->map(fn (array $step): array => [
                            'approver_id' => (int) $step['approver_id'],
                            'approval_criteria' => $step['approval_criteria'] ?? null,
                        ])
                        ->values()
                        ->all();

                    resolve(MilestoneService::class)->submitForApproval($record, $steps);

                    Notification::make()
                        ->title(__('app.notifications.approval_submitted'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}

