<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('app.labels.customer_information'))
                ->schema([
                    TextEntry::make('type')
                        ->label(__('app.labels.type'))
                        ->badge()
                        ->color(fn (string $state): string => $state === 'company' ? 'primary' : 'info')
                        ->formatStateUsing(fn (string $state): string => $state === 'company' ? 'Organization' : 'Person'),
                    TextEntry::make('name')
                        ->label(__('app.labels.name')),
                    TextEntry::make('email')
                        ->label(__('app.labels.email'))
                        ->copyable()
                        ->icon('heroicon-o-envelope'),
                    TextEntry::make('phone')
                        ->label(__('app.labels.phone'))
                        ->copyable()
                        ->icon('heroicon-o-phone'),
                    TextEntry::make('created_at')
                        ->label(__('app.labels.created_at'))
                        ->dateTime()
                        ->since(),
                ])
                ->columns(2),
        ]);
    }
}
