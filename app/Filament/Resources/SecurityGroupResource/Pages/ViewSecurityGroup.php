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
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;

final class ViewSecurityGroup extends ViewRecord
{
    protected static string $resource = SecurityGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('send_broadcast')
                ->label(__('app.actions.send_broadcast'))
                ->icon('heroicon-o-megaphone')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('subject')
                        ->label(__('app.labels.subject'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('message')
                        ->label(__('app.labels.message'))
                        ->required()
                        ->rows(4),
                    Forms\Components\Select::make('priority')
                        ->label(__('app.labels.priority'))
                        ->options([
                            'low' => __('app.labels.low'),
                            'normal' => __('app.labels.normal'),
                            'high' => __('app.labels.high'),
                            'urgent' => __('app.labels.urgent'),
                        ])
                        ->default('normal'),
                    Forms\Components\Toggle::make('include_subgroups')
                        ->label(__('app.labels.include_subgroups'))
                        ->default(true),
                    Forms\Components\Toggle::make('require_acknowledgment')
                        ->label(__('app.labels.require_acknowledgment')),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->sendBroadcastMessage(
                        $data['subject'],
                        $data['message'],
                        [
                            'priority' => $data['priority'],
                            'include_subgroups' => $data['include_subgroups'],
                            'require_acknowledgment' => $data['require_acknowledgment'],
                        ],
                    );

                    Notification::make()
                        ->title(__('app.notifications.broadcast_sent'))
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => $this->getRecord()->enable_broadcast),
            Action::make('view_hierarchy')
                ->label(__('app.actions.view_hierarchy'))
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->modalHeading(__('app.modals.group_hierarchy'))
                ->modalContent(view('filament.modals.group-hierarchy-tree', [
                    'group' => $this->getRecord(),
                    'ancestors' => $this->getRecord()->ancestors(),
                    'descendants' => $this->getRecord()->descendants,
                ]))
                ->modalWidth('4xl'),
            Action::make('export_permissions')
                ->label(__('app.actions.export_permissions'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $group = $this->getRecord();
                    $service = resolve(SecurityGroupService::class);

                    return response()->streamDownload(function () use ($group): void {
                        $data = [
                            'group' => $group->toArray(),
                            'members' => $group->members()->with('pivot')->get()->toArray(),
                            'record_access' => $group->recordAccess()->with('record')->get()->toArray(),
                            'hierarchy' => [
                                'ancestors' => $group->ancestors()->toArray(),
                                'descendants' => $group->descendants()->toArray(),
                            ],
                        ];

                        echo json_encode($data, JSON_PRETTY_PRINT);
                    }, "security-group-{$group->id}-permissions.json");
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Components\Section::make(__('app.labels.basic_information'))
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label(__('app.labels.name')),
                        Components\TextEntry::make('description')
                            ->label(__('app.labels.description'))
                            ->markdown(),
                        Components\TextEntry::make('parent.name')
                            ->label(__('app.labels.parent_group'))
                            ->placeholder(__('app.labels.root_level')),
                        Components\TextEntry::make('level')
                            ->label(__('app.labels.hierarchy_level')),
                        Components\TextEntry::make('path')
                            ->label(__('app.labels.hierarchy_path'))
                            ->placeholder(__('app.labels.root_level')),
                    ])
                    ->columns(2),

                Components\Section::make(__('app.labels.security_settings'))
                    ->schema([
                        Components\IconEntry::make('is_security_group')
                            ->label(__('app.labels.security_group'))
                            ->boolean(),
                        Components\IconEntry::make('inherit_permissions')
                            ->label(__('app.labels.inherit_permissions'))
                            ->boolean(),
                        Components\IconEntry::make('owner_only_access')
                            ->label(__('app.labels.owner_only_access'))
                            ->boolean(),
                        Components\IconEntry::make('group_only_access')
                            ->label(__('app.labels.group_only_access'))
                            ->boolean(),
                        Components\IconEntry::make('is_primary_group')
                            ->label(__('app.labels.primary_group'))
                            ->boolean(),
                        Components\IconEntry::make('active')
                            ->label(__('app.labels.active'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Components\Section::make(__('app.labels.statistics'))
                    ->schema([
                        Components\TextEntry::make('members_count')
                            ->label(__('app.labels.total_members'))
                            ->state(fn (SecurityGroup $record): int => $record->members()->count()),
                        Components\TextEntry::make('direct_children_count')
                            ->label(__('app.labels.direct_children'))
                            ->state(fn (SecurityGroup $record): int => $record->children()->count()),
                        Components\TextEntry::make('total_descendants_count')
                            ->label(__('app.labels.total_descendants'))
                            ->state(fn (SecurityGroup $record): int => $record->descendants()->count()),
                        Components\TextEntry::make('record_access_count')
                            ->label(__('app.labels.record_access_grants'))
                            ->state(fn (SecurityGroup $record): int => $record->recordAccess()->count()),
                        Components\TextEntry::make('broadcast_messages_count')
                            ->label(__('app.labels.broadcast_messages'))
                            ->state(fn (SecurityGroup $record): int => $record->broadcastMessages()->count()),
                        Components\TextEntry::make('audit_logs_count')
                            ->label(__('app.labels.audit_logs'))
                            ->state(fn (SecurityGroup $record): int => $record->auditLogs()->count()),
                    ])
                    ->columns(3),

                Components\Section::make(__('app.labels.permissions'))
                    ->schema([
                        Components\KeyValueEntry::make('record_level_permissions')
                            ->label(__('app.labels.record_level_permissions')),
                        Components\KeyValueEntry::make('field_level_permissions')
                            ->label(__('app.labels.field_level_permissions')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.customization'))
                    ->schema([
                        Components\KeyValueEntry::make('layout_overrides')
                            ->label(__('app.labels.layout_overrides')),
                        Components\KeyValueEntry::make('custom_layouts')
                            ->label(__('app.labels.custom_layouts')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.automation'))
                    ->schema([
                        Components\KeyValueEntry::make('auto_assignment_rules')
                            ->label(__('app.labels.auto_assignment_rules')),
                        Components\KeyValueEntry::make('mass_assignment_settings')
                            ->label(__('app.labels.mass_assignment_settings')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.broadcast_settings'))
                    ->schema([
                        Components\IconEntry::make('enable_broadcast')
                            ->label(__('app.labels.broadcast_enabled'))
                            ->boolean(),
                        Components\KeyValueEntry::make('broadcast_settings')
                            ->label(__('app.labels.broadcast_settings')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.login_as_settings'))
                    ->schema([
                        Components\IconEntry::make('allow_login_as')
                            ->label(__('app.labels.login_as_enabled'))
                            ->boolean(),
                        Components\KeyValueEntry::make('login_as_permissions')
                            ->label(__('app.labels.login_as_permissions')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Components\KeyValueEntry::make('metadata')
                            ->label(__('app.labels.metadata')),
                        Components\TextEntry::make('created_at')
                            ->label(__('app.labels.created_at'))
                            ->dateTime(),
                        Components\TextEntry::make('updated_at')
                            ->label(__('app.labels.updated_at'))
                            ->dateTime(),
                    ])
                    ->collapsible(),
            ]);
    }
}
