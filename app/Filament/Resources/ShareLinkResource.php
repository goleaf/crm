<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ShareLinkResource\Pages;
use App\Services\ShareLink\ShareLinkService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Grazulex\ShareLink\Models\ShareLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

final class ShareLinkResource extends Resource
{
    protected static ?string $model = ShareLink::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.share_links');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.share_link');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.share_links');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('app.labels.link_details'))
                    ->schema([
                        Forms\Components\TextInput::make('token')
                            ->label(__('app.labels.token'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label(__('app.labels.expires_at'))
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\TextInput::make('max_clicks')
                            ->label(__('app.labels.max_clicks'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('app.helpers.max_clicks')),

                        Forms\Components\TextInput::make('password')
                            ->label(__('app.labels.password'))
                            ->password()
                            ->revealable()
                            ->helperText(__('app.helpers.sharelink_password')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label(__('app.labels.metadata'))
                            ->keyLabel(__('app.labels.key'))
                            ->valueLabel(__('app.labels.value'))
                            ->addActionLabel(__('app.actions.add_metadata')),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('token')
                    ->label(__('app.labels.token'))
                    ->copyable()
                    ->copyMessage(__('app.messages.token_copied'))
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->token),

                Tables\Columns\TextColumn::make('resource')
                    ->label(__('app.labels.resource'))
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state) && isset($state['type'])) {
                            $type = class_basename($state['type']);
                            $id = $state['id'] ?? '?';

                            return "{$type} #{$id}";
                        }

                        return '—';
                    })
                    ->searchable(false),

                Tables\Columns\IconColumn::make('has_password')
                    ->label(__('app.labels.protected'))
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => ! is_null($record->password))
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('click_count')
                    ->label(__('app.labels.clicks'))
                    ->badge()
                    ->color(fn ($state, $record): string => match (true) {
                        $record->max_clicks && $state >= $record->max_clicks => 'danger',
                        $state > 0 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state, $record): string {
                        if ($record->max_clicks) {
                            return "{$state} / {$record->max_clicks}";
                        }

                        return (string) $state;
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('app.labels.expires_at'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray')
                    ->icon(fn ($state): ?string => $state && $state->isPast() ? 'heroicon-o-clock' : null),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->getStateUsing(function (\Grazulex\ShareLink\Models\ShareLink $record) {
                        $service = resolve(ShareLinkService::class);

                        return $service->isLinkActive($record);
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('app.labels.active'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('revoked_at')
                            ->where(function (\Illuminate\Contracts\Database\Query\Builder $q): void {
                                $q->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            }),
                        false: fn (Builder $query) => $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q): void {
                            $q->whereNotNull('revoked_at')
                                ->orWhere(function (\Illuminate\Contracts\Database\Query\Builder $q2): void {
                                    $q2->whereNotNull('expires_at')
                                        ->where('expires_at', '<=', now());
                                });
                        }),
                    ),

                Tables\Filters\TernaryFilter::make('has_password')
                    ->label(__('app.labels.password_protected'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('password'),
                        false: fn (Builder $query) => $query->whereNull('password'),
                    ),

                Tables\Filters\Filter::make('expires_soon')
                    ->label(__('app.labels.expires_soon'))
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '>', now())
                        ->where('expires_at', '<=', now()->addDays(7)),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label(__('app.actions.copy_url'))
                    ->icon('heroicon-o-clipboard-document')
                    ->action(function ($record): void {
                        $url = URL::route('sharelink.show', ['token' => $record->token]);
                        // Copy to clipboard handled by Filament
                    })
                    ->copyable(fn ($record) => URL::route('sharelink.show', ['token' => $record->token]))
                    ->copyMessage(__('app.messages.url_copied'))
                    ->color('gray'),

                Tables\Actions\Action::make('extend')
                    ->label(__('app.actions.extend'))
                    ->icon('heroicon-o-clock')
                    ->form([
                        Forms\Components\DateTimePicker::make('new_expires_at')
                            ->label(__('app.labels.new_expiry'))
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->minDate(now()),
                    ])
                    ->action(function (\Grazulex\ShareLink\Models\ShareLink $record, array $data, ShareLinkService $service): void {
                        $service->extendLink($record, $data['new_expires_at']);

                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.link_extended'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => ! $record->revoked_at)
                    ->color('success'),

                Tables\Actions\Action::make('revoke')
                    ->label(__('app.actions.revoke'))
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function (\Grazulex\ShareLink\Models\ShareLink $record, ShareLinkService $service): void {
                        $service->revokeLink($record);

                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.link_revoked'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => ! $record->revoked_at)
                    ->color('danger'),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('revoke')
                    ->label(__('app.actions.revoke_selected'))
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function ($records, ShareLinkService $service): void {
                        foreach ($records as $record) {
                            $service->revokeLink($record);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.links_revoked'))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('danger'),

                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShareLinks::route('/'),
            'view' => Pages\ViewShareLink::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                config('sharelink.user_tracking.enabled') && ! auth()->user()->can('view_all_sharelinks'),
                fn (Builder $query) => $query->where('created_by', auth()->id()),
            );
    }
}
