<?php

declare(strict_types=1);

namespace App\Filament\Resources\Accounts\Pages;

use App\Data\AddressData;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Support\Addresses\AddressFormatter;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

final class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make([
                    Grid::make()
                        ->columns(12)
                        ->schema([
                            TextEntry::make('name')
                                ->label(__('app.labels.name'))
                                ->columnSpan(6)
                                ->size('lg')
                                ->weight('bold'),
                            TextEntry::make('type')
                                ->label(__('app.labels.account_type'))
                                ->badge()
                                ->columnSpan(3)
                                ->formatStateUsing(fn (?AccountType $state): string => $state?->label() ?? '—')
                                ->color(fn (?AccountType $state): string => $state?->color() ?? 'gray'),
                            TextEntry::make('industry')
                                ->label(__('app.labels.industry'))
                                ->columnSpan(3)
                                ->formatStateUsing(fn (?Industry $state): string => $state?->label() ?? '—'),
                            TextEntry::make('owner.name')
                                ->label(__('app.labels.owner'))
                                ->columnSpan(4)
                                ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                            TextEntry::make('assignedTo.name')
                                ->label(__('app.labels.assigned_to'))
                                ->columnSpan(4)
                                ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                            TextEntry::make('currency')
                                ->label(__('app.labels.currency'))
                                ->columnSpan(4)
                                ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                            TextEntry::make('website')
                                ->label(__('app.labels.website'))
                                ->url(fn (?string $state): ?string => $state ?: null)
                                ->columnSpan(6)
                                ->formatStateUsing(fn (?string $state): string => $state ?: '—'),
                            TextEntry::make('annual_revenue')
                                ->label(__('app.labels.annual_revenue'))
                                ->columnSpan(3)
                                ->formatStateUsing(fn (mixed $state): string => $state !== null ? number_format((float) $state, 2) : '—'),
                            TextEntry::make('employee_count')
                                ->label(__('app.labels.employees'))
                                ->columnSpan(3)
                                ->formatStateUsing(fn (mixed $state): string => $state !== null ? number_format((int) $state) : '—'),
                            RepeatableEntry::make('addresses')
                                ->label(__('app.labels.addresses'))
                                ->columnSpan(12)
                                ->state(fn (Account $record): array => $record->addressCollection()
                                    ->map(fn (AddressData $address): array => [
                                        'label' => $address->label ?? $address->type->label(),
                                        'formatted' => new AddressFormatter()->format($address, multiline: true),
                                    ])
                                    ->all())
                                ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                                ->table([
                                    TableColumn::make(__('app.labels.label')),
                                    TableColumn::make(__('app.labels.address')),
                                ])
                                ->schema([
                                    TextEntry::make('label')
                                        ->label(__('app.labels.label')),
                                    TextEntry::make('formatted')
                                        ->label(__('app.labels.address')),
                                ]),
                            RepeatableEntry::make('activity_timeline')
                                ->label(__('app.labels.activity_timeline'))
                                ->columnSpan(12)
                                ->state(fn (Account $record): array => $record->getActivityTimeline()
                                    ->map(fn (array $item): array => [
                                        'title' => $item['title'],
                                        'summary' => $item['summary'],
                                        'type' => ucfirst((string) $item['type']),
                                        'created_at' => $item['created_at'],
                                    ])
                                    ->all())
                                ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                                ->table([
                                    TableColumn::make(__('app.labels.entry')),
                                    TableColumn::make(__('app.labels.type')),
                                    TableColumn::make(__('app.labels.summary')),
                                    TableColumn::make(__('app.labels.when'))
                                        ->alignment(Alignment::End),
                                ])
                                ->schema([
                                    TextEntry::make('title')
                                        ->label(__('app.labels.entry')),
                                    TextEntry::make('type')
                                        ->label(__('app.labels.type'))
                                        ->badge(),
                                    TextEntry::make('summary')
                                        ->label(__('app.labels.summary')),
                                    TextEntry::make('created_at')
                                        ->label(__('app.labels.when'))
                                        ->since(),
                                ]),
                        ]),
                ]),
                Section::make([
                    TextEntry::make('created_at')
                        ->label(__('app.labels.created_date'))
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label(__('app.labels.last_updated'))
                        ->dateTime(),
                ])->grow(false),
            ]);
    }
}
