<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\RelationManagers;

use App\Models\User;
use App\Services\SecurityGroup\SecurityGroupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

final class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('app.labels.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?string $operation): bool => $operation === 'edit'),
                Forms\Components\Toggle::make('is_owner')
                    ->label(__('app.labels.is_owner'))
                    ->helperText(__('app.helpers.group_owner')),
                Forms\Components\Toggle::make('is_admin')
                    ->label(__('app.labels.is_admin'))
                    ->helperText(__('app.helpers.group_admin')),
                Forms\Components\Toggle::make('inherit_from_parent')
                    ->label(__('app.labels.inherit_from_parent'))
                    ->default(true)
                    ->helperText(__('app.helpers.inherit_permissions')),
                Forms\Components\Toggle::make('can_manage_members')
                    ->label(__('app.labels.can_manage_members'))
                    ->helperText(__('app.helpers.manage_members_permission')),
                Forms\Components\Toggle::make('can_assign_records')
                    ->label(__('app.labels.can_assign_records'))
                    ->helperText(__('app.helpers.assign_records_permission')),
                Forms\Components\KeyValue::make('permission_overrides')
                    ->label(__('app.labels.permission_overrides'))
                    ->keyLabel(__('app.labels.permission'))
                    ->valueLabel(__('app.labels.value'))
                    ->helperText(__('app.helpers.permission_overrides')),
                Forms\Components\Textarea::make('notes')
                    ->label(__('app.labels.notes'))
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('app.labels.email'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_owner')
                    ->label(__('app.labels.owner'))
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->pivot->is_owner ?? false),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label(__('app.labels.admin'))
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->pivot->is_admin ?? false),
                Tables\Columns\IconColumn::make('can_manage_members')
                    ->label(__('app.labels.can_manage'))
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->pivot->can_manage_members ?? false),
                Tables\Columns\IconColumn::make('can_assign_records')
                    ->label(__('app.labels.can_assign'))
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->pivot->can_assign_records ?? false),
                Tables\Columns\TextColumn::make('joined_at')
                    ->label(__('app.labels.joined_at'))
                    ->dateTime()
                    ->getStateUsing(fn ($record): ?\Carbon\Carbon => $record->pivot->joined_at),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_owner')
                    ->label(__('app.labels.owners_only'))
                    ->queries(
                        true: fn ($query) => $query->wherePivot('is_owner', true),
                        false: fn ($query) => $query->wherePivot('is_owner', false),
                    ),
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label(__('app.labels.admins_only'))
                    ->queries(
                        true: fn ($query) => $query->wherePivot('is_admin', true),
                        false: fn ($query) => $query->wherePivot('is_admin', false),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['joined_at'] = now();
                        $data['added_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function ($record, array $data): void {
                        $service = resolve(SecurityGroupService::class);
                        $user = User::find($data['user_id']);

                        // Log the addition
                        $this->getOwnerRecord()->logAudit('member_added', 'membership', $user->id, [], $data);

                        Notification::make()
                            ->title(__('app.notifications.member_added'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (\App\Models\User $record, array $data): void {
                        $service = resolve(SecurityGroupService::class);
                        $service->updateMemberPermissions($this->getOwnerRecord(), $record, $data);

                        Notification::make()
                            ->title(__('app.notifications.member_updated'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (\App\Models\User $record): void {
                        $service = resolve(SecurityGroupService::class);
                        $service->removeMemberFromGroup($this->getOwnerRecord(), $record);

                        Notification::make()
                            ->title(__('app.notifications.member_removed'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('login_as')
                    ->label(__('app.actions.login_as'))
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('app.modals.login_as_user'))
                    ->modalDescription(__('app.modals.login_as_description'))
                    ->action(function ($record): void {
                        // This would implement the login-as functionality
                        // For now, just show a notification
                        Notification::make()
                            ->title(__('app.notifications.login_as_feature'))
                            ->body(__('app.notifications.login_as_not_implemented'))
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (): bool => $this->getOwnerRecord()->allow_login_as),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('make_admin')
                        ->label(__('app.actions.make_admin'))
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $this->getOwnerRecord()->members()->updateExistingPivot($record->id, [
                                    'is_admin' => true,
                                ]);
                            }

                            Notification::make()
                                ->title(__('app.notifications.members_promoted'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('remove_admin')
                        ->label(__('app.actions.remove_admin'))
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $this->getOwnerRecord()->members()->updateExistingPivot($record->id, [
                                    'is_admin' => false,
                                ]);
                            }

                            Notification::make()
                                ->title(__('app.notifications.admin_removed'))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
