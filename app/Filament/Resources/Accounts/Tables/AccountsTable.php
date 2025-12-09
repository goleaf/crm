<?php

declare(strict_types=1);

namespace App\Filament\Resources\Accounts\Tables;

use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Account;
use App\Support\Addresses\AddressFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Account Type')
                    ->badge()
                    ->formatStateUsing(fn (?AccountType $state): string => $state?->label() ?? '—')
                    ->color(fn (?AccountType $state): string => $state?->color() ?? 'gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('industry')
                    ->label('Industry')
                    ->formatStateUsing(fn (?Industry $state): string => $state?->label() ?? '—')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('annual_revenue')
                    ->label('Annual Revenue')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (Account $record): string => $record->annual_revenue !== null
                        ? ($record->currency ?? 'USD') . ' ' . number_format((float) $record->annual_revenue, 2)
                        : '—'),
                TextColumn::make('employee_count')
                    ->label('Employees')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')
                    ->label('Children')
                    ->counts('children')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_address')
                    ->label('Billing Address')
                    ->state(fn (Account $record): string => self::formatAddressFor($record, AddressType::BILLING))
                    ->toggleable(),
                TextColumn::make('shipping_address')
                    ->label('Shipping Address')
                    ->state(fn (Account $record): string => self::formatAddressFor($record, AddressType::SHIPPING))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('website')
                    ->label('Website')
                    ->url(fn (Account $record): ?string => $record->website ?: null)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Account Type')
                    ->options(AccountType::options())
                    ->multiple(),
                SelectFilter::make('industry')
                    ->label('Industry')
                    ->options(Industry::options())
                    ->multiple(),
                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options(config('company.currency_codes'))
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function formatAddressFor(Account $record, AddressType $type): string
    {
        $formatter = new AddressFormatter;
        $address = $record->addressFor($type);

        if ($address instanceof \App\Data\AddressData) {
            return $formatter->format($address);
        }

        $legacy = $type === AddressType::BILLING ? $record->billing_address : $record->shipping_address;

        return $formatter->format($legacy);
    }
}
