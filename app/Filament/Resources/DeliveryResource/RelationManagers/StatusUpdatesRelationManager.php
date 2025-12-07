<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryResource\RelationManagers;

use App\Enums\DeliveryStatus;
use App\Models\DeliveryStatusHistory;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class StatusUpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusUpdates';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-arrow-path-rounded-square';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('from_status')
                ->label('From')
                ->options(DeliveryStatus::class)
                ->default(fn (): ?DeliveryStatus => $this->getOwnerRecord()?->status)
                ->native(false),
            Select::make('to_status')
                ->label('To')
                ->options(DeliveryStatus::class)
                ->required()
                ->native(false),
            Textarea::make('note')
                ->label('Notes')
                ->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                TextColumn::make('from_status')
                    ->label('From')
                    ->badge()
                    ->formatStateUsing(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getLabel() : (DeliveryStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getColor() : (DeliveryStatus::tryFrom((string) $state)?->getColor() ?? 'gray')),
                TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getLabel() : (DeliveryStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getColor() : (DeliveryStatus::tryFrom((string) $state)?->getColor() ?? 'gray')),
                TextColumn::make('note')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('changedBy.name')
                    ->label('Updated By')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Updated At')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Model&\App\Models\Delivery $owner */
                        $owner = $this->getOwnerRecord();
                        $toStatus = DeliveryStatus::tryFrom((string) $data['to_status']);

                        return [
                            ...$data,
                            'team_id' => $owner->team_id,
                            'from_status' => $data['from_status'] ?? $owner->status?->value,
                            'to_status' => $toStatus?->value ?? $data['to_status'],
                            'changed_by' => auth('web')->id(),
                        ];
                    })
                    ->after(function (DeliveryStatusHistory $record): void {
                        $delivery = $record->delivery;

                        if ($delivery === null) {
                            return;
                        }

                        $status = $record->to_status instanceof DeliveryStatus
                            ? $record->to_status
                            : DeliveryStatus::tryFrom((string) $record->to_status);

                        $delivery::withoutEvents(function () use ($delivery, $record, $status): void {
                            $delivery->forceFill([
                                'status' => $status?->value ?? $record->to_status,
                                'cancelled_at' => $status === DeliveryStatus::CANCELLED ? now() : $delivery->cancelled_at,
                                'delivered_at' => $status === DeliveryStatus::DELIVERED ? now() : $delivery->delivered_at,
                            ])->saveQuietly();
                        });
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
