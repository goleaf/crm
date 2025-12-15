<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\DeliveryStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Delivery #')->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => DeliveryStatus::PENDING->value,
                        'primary' => DeliveryStatus::SHIPPED->value,
                        'success' => DeliveryStatus::DELIVERED->value,
                    ])
                    ->formatStateUsing(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getLabel() : (string) $state)
                    ->sortable(),
                TextColumn::make('tracking_number')->label('Tracking'),
                TextColumn::make('shipped_at')->dateTime(),
                TextColumn::make('delivered_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ]);
    }
}
