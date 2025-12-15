<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use App\Filament\Support\UploadConstraints;
use App\Support\Paths\StoragePaths;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-queue-list';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                UploadConstraints::apply(
                    FileUpload::make('file_path')
                        ->label('File')
                        ->disk('public')
                        ->directory(fn (): string => StoragePaths::documentsDirectory($this->ownerRecord?->team_id))
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => StoragePaths::documentFileName(
                                $file->getClientOriginalName(),
                            ),
                        )
                        ->required(),
                    types: ['documents', 'images'],
                ),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->limit(40),
                TextColumn::make('notes')
                    ->limit(40),
                TextColumn::make('uploader.name')
                    ->label('Uploaded by')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
