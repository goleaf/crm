<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAttributeResource\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributeResource\Pages\EditProductAttribute;
use App\Filament\Resources\ProductAttributeResource\Pages\ListProductAttributes;
use App\Filament\Support\SlugHelper;
use App\Models\ProductAttribute;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Attribute')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(SlugHelper::updateSlug()),
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->helperText('Automatically generated if left blank.')
                                    ->rules(['nullable', 'slug'])
                                    ->maxLength(255),
                                Select::make('data_type')
                                    ->label('Data type')
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'boolean' => 'Boolean',
                                        'select' => 'Select',
                                    ])
                                    ->default('text')
                                    ->required()
                                    ->native(false),
                                Toggle::make('is_configurable')
                                    ->label('Configurable')
                                    ->helperText('Make this attribute available for variations.'),
                                Toggle::make('is_filterable')
                                    ->label('Filterable'),
                                Toggle::make('is_required')
                                    ->label('Required'),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->columnSpan(5),
                        Section::make('Values')
                            ->schema([
                                Repeater::make('values')
                                    ->relationship()
                                    ->addActionLabel('Add value')
                                    ->table([
                                        TableColumn::make('Value')
                                            ->markAsRequired(),
                                        TableColumn::make('Code'),
                                        TableColumn::make('Order')
                                            ->alignment(Alignment::End),
                                    ])
                                    ->compact()
                                    ->schema([
                                        TextInput::make('value')
                                            ->label('Value')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->label('Code')
                                            ->maxLength(100),
                                        TextInput::make('sort_order')
                                            ->label('Order')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(7),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('data_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_configurable')
                    ->label('Configurable')
                    ->boolean(),
                IconColumn::make('is_filterable')
                    ->label('Filterable')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('values_count')
                    ->label('Values')
                    ->counts('values')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('data_type')
                    ->label('Type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Boolean',
                        'select' => 'Select',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'edit' => EditProductAttribute::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<ProductAttribute>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
