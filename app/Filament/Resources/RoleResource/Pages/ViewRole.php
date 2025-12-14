<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Role;
use App\Services\Role\RoleManagementService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;

final class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('create_from_template')
                ->label(__('app.actions.create_from_template'))
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn (Role $record) => $record->is_template)
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label(__('app.labels.name'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('display_name')
                        ->label(__('app.labels.display_name')),
                ])
                ->action(function (Role $record, array $data): void {
                    $service = resolve(RoleManagementService::class);
                    $newRole = $service->createFromTemplate($record, $data);

                    $this->redirect(self::getResource()::getUrl('edit', ['record' => $newRole]));
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Section::make(__('app.labels.basic_information'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('display_name')
                                    ->label(__('app.labels.display_name')),

                                Components\TextEntry::make('name')
                                    ->label(__('app.labels.name'))
                                    ->badge(),
                            ]),

                        Components\TextEntry::make('description')
                            ->label(__('app.labels.description'))
                            ->columnSpanFull(),

                        Components\Grid::make(3)
                            ->schema([
                                Components\IconEntry::make('is_template')
                                    ->label(__('app.labels.is_template'))
                                    ->boolean(),

                                Components\IconEntry::make('is_admin_role')
                                    ->label(__('app.labels.is_admin_role'))
                                    ->boolean(),

                                Components\IconEntry::make('is_studio_role')
                                    ->label(__('app.labels.is_studio_role'))
                                    ->boolean(),
                            ]),
                    ]),

                Components\Section::make(__('app.labels.inheritance'))
                    ->schema([
                        Components\TextEntry::make('parentRole.display_name')
                            ->label(__('app.labels.parent_role'))
                            ->placeholder(__('app.placeholders.no_parent_role')),

                        Components\RepeatableEntry::make('childRoles')
                            ->label(__('app.labels.child_roles'))
                            ->schema([
                                Components\TextEntry::make('display_name')
                                    ->label(__('app.labels.name')),
                            ])
                            ->placeholder(__('app.placeholders.no_child_roles')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.permissions'))
                    ->schema([
                        Components\RepeatableEntry::make('permissions')
                            ->label(__('app.labels.permissions'))
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->formatStateUsing(fn (string $state): string => str_replace([':', '_'], [' → ', ' '], title_case($state)),
                                    )
                                    ->badge(),
                            ])
                            ->columns(3)
                            ->placeholder(__('app.placeholders.no_permissions')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.users'))
                    ->schema([
                        Components\RepeatableEntry::make('users')
                            ->label(__('app.labels.assigned_users'))
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label(__('app.labels.name')),
                                Components\TextEntry::make('email')
                                    ->label(__('app.labels.email')),
                            ])
                            ->columns(2)
                            ->placeholder(__('app.placeholders.no_users_assigned')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.audit_trail'))
                    ->schema([
                        Components\RepeatableEntry::make('auditLogs')
                            ->label(__('app.labels.recent_changes'))
                            ->schema([
                                Components\TextEntry::make('action')
                                    ->label(__('app.labels.action'))
                                    ->badge(),
                                Components\TextEntry::make('user.name')
                                    ->label(__('app.labels.user')),
                                Components\TextEntry::make('created_at')
                                    ->label(__('app.labels.date'))
                                    ->dateTime(),
                            ])
                            ->columns(3)
                            ->limit(10)
                            ->placeholder(__('app.placeholders.no_audit_logs')),
                    ])
                    ->collapsible(),

                Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Components\KeyValueEntry::make('metadata')
                            ->label(__('app.labels.metadata'))
                            ->placeholder(__('app.placeholders.no_metadata')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
