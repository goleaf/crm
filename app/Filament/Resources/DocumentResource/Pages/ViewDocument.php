<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;

final class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Document')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title'),
                                TextEntry::make('visibility')
                                    ->badge(),
                                TextEntry::make('template.name')
                                    ->label('Template')
                                    ->placeholder('—'),
                                TextEntry::make('description')
                                    ->columnSpan(3)
                                    ->placeholder('—'),
                                TextEntry::make('currentVersion.version')
                                    ->label('Current version'),
                                TextEntry::make('currentVersion.updated_at')
                                    ->label('Version updated at')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
