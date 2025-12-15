<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EmployeeStatus;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?int $navigationSort = 10;

    protected static UnitEnum|string|null $navigationGroup = 'Resources';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('mobile')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('employee_number')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(EmployeeStatus::class)
                                    ->required()
                                    ->default(EmployeeStatus::ACTIVE->value),
                                Forms\Components\Select::make('manager_id')
                                    ->relationship('manager', 'first_name')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => $record->full_name),
                                Forms\Components\TextInput::make('department')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('role')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('title')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('start_date'),
                                Forms\Components\DatePicker::make('end_date'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Link this employee to a user account for portal access'),
                            ]),
                    ]),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('state')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('postal_code')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('country')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Emergency Contact')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('emergency_contact_name')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('emergency_contact_relationship')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Skills & Certifications')
                    ->schema([
                        Forms\Components\TagsInput::make('skills')
                            ->placeholder('Add skills')
                            ->helperText('Press enter to add each skill'),
                        Forms\Components\Repeater::make('certifications')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Certification Name'),
                                Forms\Components\TextInput::make('issuer')
                                    ->label('Issuing Organization'),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Issue Date'),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Expiry Date'),
                            ])
                            ->columns(2)
                            ->collapsible(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Performance')
                    ->schema([
                        Forms\Components\Textarea::make('performance_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('performance_rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->suffix('/ 5.0'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Time Off')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('vacation_days_total')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                                Forms\Components\TextInput::make('vacation_days_used')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days')
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('sick_days_total')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                                Forms\Components\TextInput::make('sick_days_used')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('System Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('has_portal_access')
                                    ->label('Portal Access')
                                    ->helperText('Allow this employee to access the portal'),
                                Forms\Components\TextInput::make('capacity_hours_per_week')
                                    ->numeric()
                                    ->default(40)
                                    ->suffix('hours')
                                    ->helperText('Standard working hours per week'),
                                Forms\Components\TextInput::make('payroll_id')
                                    ->maxLength(255)
                                    ->helperText('External payroll system ID'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('manager.full_name')
                    ->label('Manager')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => EmployeeStatus::from($state)->label())
                    ->color(fn (string $state): string => EmployeeStatus::from($state)->color()),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('has_portal_access')
                    ->label('Portal')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EmployeeStatus::class),
                Tables\Filters\SelectFilter::make('department')
                    ->options(fn (): array => Employee::query()
                        ->whereNotNull('department')
                        ->distinct()
                        ->pluck('department', 'department')
                        ->toArray()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('first_name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
