<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Relaticle\CustomFields\Facades\CustomFields;

final class PeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'people';

    protected static ?string $modelLabel = 'person';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-user';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('job_title')
                    ->label('Job Title')
                    ->maxLength(255),
                TextInput::make('primary_email')
                    ->label('Primary Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone_mobile')
                    ->label('Mobile')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('lead_source')
                    ->label('Lead Source')
                    ->maxLength(255),
                TagsInput::make('segments')
                    ->label('Segments')
                    ->suggestions(config('contacts.segment_suggestions', [])),
                CustomFields::form()->forSchema($schema)->build()
                    ->columnSpanFull()
                    ->columns(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('job_title')
                    ->label('Job Title')
                    ->toggleable(),
                TextColumn::make('primary_email')
                    ->label('Email')
                    ->toggleable(),
                TextColumn::make('phone_mobile')
                    ->label('Mobile')
                    ->toggleable(),
                TextColumn::make('lead_source')
                    ->label('Lead Source')
                    ->toggleable(),

                ...CustomFields::table()->forModel($table->getModel())->columns(),
            ])
            ->filters([
                //
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
