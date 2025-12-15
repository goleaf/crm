<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\Pages;

use App\Filament\Resources\SecurityGroupResource;
use App\Models\SecurityGroup;
use App\Services\SecurityGroup\SecurityGroupService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListSecurityGroups extends ListRecords
{
    protected static string $resource = SecurityGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('view_hierarchy')
                ->label(__('app.actions.view_hierarchy'))
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->modalHeading(__('app.modals.security_group_hierarchy'))
                ->modalContent(view('filament.modals.security-group-hierarchy', [
                    'groups' => resolve(SecurityGroupService::class)->getGroupHierarchy(
                        auth()->user()->currentTeam->id,
                    ),
                ]))
                ->modalWidth('5xl'),
            Action::make('mass_assignment')
                ->label(__('app.actions.mass_assignment'))
                ->icon('heroicon-o-users')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('group_id')
                        ->label(__('app.labels.security_group'))
                        ->options(SecurityGroup::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    Forms\Components\Select::make('record_type')
                        ->label(__('app.labels.record_type'))
                        ->options([
                            \App\Models\Company::class => __('app.labels.companies'),
                            \App\Models\People::class => __('app.labels.people'),
                            \App\Models\Opportunity::class => __('app.labels.opportunities'),
                            \App\Models\Task::class => __('app.labels.tasks'),
                            \App\Models\Note::class => __('app.labels.notes'),
                        ])
                        ->required(),
                    Forms\Components\Select::make('access_level')
                        ->label(__('app.labels.access_level'))
                        ->options([
                            'read' => __('app.labels.read'),
                            'write' => __('app.labels.write'),
                            'admin' => __('app.labels.admin'),
                            'owner' => __('app.labels.owner'),
                        ])
                        ->default('read')
                        ->required(),
                    Forms\Components\Textarea::make('filter_criteria')
                        ->label(__('app.labels.filter_criteria'))
                        ->helperText(__('app.helpers.mass_assignment_filter'))
                        ->placeholder('{"status": "active", "created_at": ">2024-01-01"}'),
                ])
                ->action(function (array $data): void {
                    $group = SecurityGroup::find($data['group_id']);
                    $recordType = $data['record_type'];

                    // Build query based on filter criteria
                    $query = $recordType::query();

                    if (! empty($data['filter_criteria'])) {
                        $criteria = json_decode((string) $data['filter_criteria'], true);
                        if ($criteria) {
                            foreach ($criteria as $field => $value) {
                                if (str_starts_with($value, '>')) {
                                    $query->where($field, '>', substr($value, 1));
                                } elseif (str_starts_with($value, '<')) {
                                    $query->where($field, '<', substr($value, 1));
                                } else {
                                    $query->where($field, $value);
                                }
                            }
                        }
                    }

                    $records = $query->get();

                    // Apply mass assignment
                    $service = resolve(SecurityGroupService::class);
                    foreach ($records as $record) {
                        $service->grantRecordAccess($group, $record, $data['access_level']);
                    }

                    Notification::make()
                        ->title(__('app.notifications.mass_assignment_completed'))
                        ->body(__('app.notifications.records_assigned_count', ['count' => $records->count()]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
