<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Customer $record): string => CustomerResource::getUrl('view', [$record]))
            ->paginated([10, 25, 50])
            ->defaultSort('created_at', 'desc')
            ->columns([
                BadgeColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->colors([
                        'primary' => 'company',
                        'info' => 'person',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'company' ? 'Organization' : 'Person')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('app.labels.email'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label(__('app.labels.phone'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\CustomerExporter::class),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Customer>
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->when(
                $tenant,
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team')
            );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
