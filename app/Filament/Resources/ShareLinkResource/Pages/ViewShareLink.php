<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShareLinkResource\Pages;

use App\Filament\Resources\ShareLinkResource;
use App\Services\ShareLink\ShareLinkService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;

final class ViewShareLink extends ViewRecord
{
    protected static string $resource = ShareLinkResource::class;

    public function infolist(Schema $schema): Schema
    {
        $service = resolve(ShareLinkService::class);
        $stats = $service->getLinkStats($this->record);

        return $schema
            ->schema([
                Infolists\Components\Section::make(__('app.labels.link_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('token')
                            ->label(__('app.labels.token'))
                            ->copyable()
                            ->copyMessage(__('app.messages.token_copied')),

                        Infolists\Components\TextEntry::make('url')
                            ->label(__('app.labels.url'))
                            ->state(fn ($record) => URL::route('sharelink.show', ['token' => $record->token]))
                            ->copyable()
                            ->copyMessage(__('app.messages.url_copied'))
                            ->url(fn ($record) => URL::route('sharelink.show', ['token' => $record->token]))
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('resource')
                            ->label(__('app.labels.resource'))
                            ->formatStateUsing(function ($state): string {
                                if (is_array($state) && isset($state['type'])) {
                                    $type = class_basename($state['type']);
                                    $id = $state['id'] ?? '?';

                                    return "{$type} #{$id}";
                                }

                                return '—';
                            }),

                        Infolists\Components\IconEntry::make('has_password')
                            ->label(__('app.labels.password_protected'))
                            ->boolean()
                            ->getStateUsing(fn ($record): bool => ! is_null($record->password))
                            ->trueIcon('heroicon-o-lock-closed')
                            ->falseIcon('heroicon-o-lock-open')
                            ->trueColor('warning')
                            ->falseColor('gray'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('app.labels.usage_statistics'))
                    ->schema([
                        Infolists\Components\TextEntry::make('click_count')
                            ->label(__('app.labels.total_clicks'))
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('remaining_clicks')
                            ->label(__('app.labels.remaining_clicks'))
                            ->state(fn () => $stats['remaining_clicks'] ?? __('app.labels.unlimited'))
                            ->badge()
                            ->color(fn (): string => $stats['remaining_clicks'] && $stats['remaining_clicks'] < 5 ? 'warning' : 'gray'),

                        Infolists\Components\TextEntry::make('first_access_at')
                            ->label(__('app.labels.first_accessed'))
                            ->dateTime()
                            ->placeholder(__('app.labels.never')),

                        Infolists\Components\TextEntry::make('last_access_at')
                            ->label(__('app.labels.last_accessed'))
                            ->dateTime()
                            ->placeholder(__('app.labels.never')),

                        Infolists\Components\TextEntry::make('last_ip')
                            ->label(__('app.labels.last_ip'))
                            ->placeholder(__('app.labels.none')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('app.labels.expiration'))
                    ->schema([
                        Infolists\Components\TextEntry::make('expires_at')
                            ->label(__('app.labels.expires_at'))
                            ->dateTime()
                            ->placeholder(__('app.labels.never'))
                            ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray'),

                        Infolists\Components\TextEntry::make('days_until_expiry')
                            ->label(__('app.labels.days_until_expiry'))
                            ->state(fn () => $stats['days_until_expiry'] ?? __('app.labels.never'))
                            ->badge()
                            ->color(function () use ($stats): string {
                                if (! isset($stats['days_until_expiry'])) {
                                    return 'gray';
                                }

                                return match (true) {
                                    $stats['days_until_expiry'] < 0 => 'danger',
                                    $stats['days_until_expiry'] < 7 => 'warning',
                                    default => 'success',
                                };
                            }),

                        Infolists\Components\TextEntry::make('revoked_at')
                            ->label(__('app.labels.revoked_at'))
                            ->dateTime()
                            ->placeholder(__('app.labels.not_revoked'))
                            ->color('danger'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('metadata')
                            ->label(__('app.labels.metadata'))
                            ->keyLabel(__('app.labels.key'))
                            ->valueLabel(__('app.labels.value')),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record): bool => ! empty($record->metadata)),

                Infolists\Components\Section::make(__('app.labels.timestamps'))
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('app.labels.created_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('app.labels.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('copy_url')
                ->label(__('app.actions.copy_url'))
                ->icon('heroicon-o-clipboard-document')
                ->copyable(fn ($record) => URL::route('sharelink.show', ['token' => $record->token]))
                ->copyMessage(__('app.messages.url_copied'))
                ->color('gray'),

            Actions\Action::make('extend')
                ->label(__('app.actions.extend'))
                ->icon('heroicon-o-clock')
                ->form([
                    \Filament\Forms\Components\DateTimePicker::make('new_expires_at')
                        ->label(__('app.labels.new_expiry'))
                        ->required()
                        ->native(false)
                        ->seconds(false)
                        ->minDate(now()),
                ])
                ->action(function (array $data, ShareLinkService $service): void {
                    $service->extendLink($this->record, $data['new_expires_at']);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.link_extended'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['expires_at']);
                })
                ->visible(fn (): bool => ! $this->record->revoked_at)
                ->color('success'),

            Actions\Action::make('revoke')
                ->label(__('app.actions.revoke'))
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->action(function (ShareLinkService $service): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse {
                    $service->revokeLink($this->record);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.link_revoked'))
                        ->success()
                        ->send();

                    return to_route('filament.app.resources.share-links.index');
                })
                ->visible(fn (): bool => ! $this->record->revoked_at)
                ->color('danger'),

            Actions\DeleteAction::make(),
        ];
    }
}
