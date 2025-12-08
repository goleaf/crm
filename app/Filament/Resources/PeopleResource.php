<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ContactEmailType;
use App\Enums\CreationSource;
use App\Enums\Industry;
use App\Filament\Exports\PeopleExporter;
use App\Filament\RelationManagers\ActivitiesRelationManager as SharedActivitiesRelationManager;
use App\Filament\Resources\PeopleResource\Pages\CreatePeople;
use App\Filament\Resources\PeopleResource\Pages\EditPeople;
use App\Filament\Resources\PeopleResource\Pages\ListPeople;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\Resources\PeopleResource\RelationManagers\CasesRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Models\People;
use App\Support\Helpers\ArrayHelper;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Facades\CustomFields;

final class PeopleResource extends Resource
{
    protected static ?string $model = People::class;

    protected static ?string $modelLabel = 'person';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact Profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('company_id')
                                    ->relationship('company', 'name')
                                    ->suffixAction(
                                        Action::make('Create Company')
                                            ->model(Company::class)
                                            ->schema(fn (Schema $schema): \Filament\Schemas\Schema => $schema->components([
                                                TextInput::make('name')
                                                    ->required(),
                                                TextInput::make('website')
                                                    ->label('Website')
                                                    ->url()
                                                    ->maxLength(255),
                                                Select::make('industry')
                                                    ->label('Industry')
                                                    ->options(Industry::options())
                                                    ->enum(Industry::class)
                                                    ->searchable()
                                                    ->native(false),
                                                TextInput::make('revenue')
                                                    ->label('Annual Revenue')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->minValue(0),
                                                TextInput::make('employee_count')
                                                    ->label('Employees')
                                                    ->integer()
                                                    ->minValue(0),
                                                Select::make('account_owner_id')
                                                    ->model(Company::class)
                                                    ->relationship('accountOwner', 'name')
                                                    ->label(__('app.labels.account_owner'))
                                                    ->preload()
                                                    ->searchable(),
                                                CustomFields::form()->forSchema($schema)->build()->columns(1),
                                            ]))
                                            ->modalWidth(Width::Large)
                                            ->slideOver()
                                            ->icon('heroicon-o-plus')
                                            ->action(function (array $data, Set $set): void {
                                                $company = Company::create($data);
                                                $set('company_id', $company->id);
                                            })
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('job_title')
                                    ->label('Job Title')
                                    ->maxLength(255),
                                TextInput::make('department')
                                    ->label('Department')
                                    ->maxLength(255),
                                Select::make('role')
                                    ->label('Role')
                                    ->options(
                                        collect(config('contacts.roles', []))
                                            ->mapWithKeys(fn (string $role): array => [$role => $role])
                                            ->all()
                                    )
                                    ->native(false)
                                    ->searchable()
                                    ->placeholder('Select role'),
                                TextInput::make('lead_source')
                                    ->label('Lead Source')
                                    ->maxLength(255)
                                    ->placeholder('Website, Referral, Event...')
                                    ->hint(ArrayHelper::joinList(config('contacts.lead_sources', []), ', ', emptyPlaceholder: null)),
                                Select::make('reports_to_id')
                                    ->relationship(
                                        'reportsTo',
                                        'name',
                                        fn (Builder $query, ?People $record): Builder => $record instanceof \App\Models\People
                                            ? $query->whereKeyNot($record->getKey())
                                            : $query
                                    )
                                    ->label('Reports To')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                    ]),
                Section::make('Communication')
                    ->schema([
                        Repeater::make('emails')
                            ->label('Emails')
                            ->relationship('emails')
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label('Type')
                                    ->options(collect(ContactEmailType::cases())->mapWithKeys(
                                        fn (ContactEmailType $type): array => [$type->value => $type->label()]
                                    ))
                                    ->default(ContactEmailType::Work)
                                    ->required()
                                    ->native(false),
                                Toggle::make('is_primary')
                                    ->label('Primary')
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone_mobile')
                                    ->label('Mobile')
                                    ->tel()
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->maxLength(50),
                                TextInput::make('phone_office')
                                    ->label('Office')
                                    ->tel()
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->maxLength(50),
                                TextInput::make('phone_home')
                                    ->label('Home')
                                    ->tel()
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->maxLength(50),
                                TextInput::make('phone_fax')
                                    ->label('Fax')
                                    ->tel()
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->maxLength(50),
                                TextInput::make('social_links.linkedin')
                                    ->label('LinkedIn')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('social_links.twitter')
                                    ->label('Twitter')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('social_links.facebook')
                                    ->label('Facebook')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('social_links.github')
                                    ->label('GitHub')
                                    ->url()
                                    ->maxLength(255),
                            ]),
                    ]),
                Section::make('Address')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address_street')
                                    ->label('Street')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('address_city')
                                    ->label('City')
                                    ->maxLength(255),
                                TextInput::make('address_state')
                                    ->label('State/Province')
                                    ->maxLength(255),
                                TextInput::make('address_postal_code')
                                    ->label('Postal Code')
                                    ->maxLength(20),
                                TextInput::make('address_country')
                                    ->label('Country')
                                    ->maxLength(100),
                            ]),
                    ]),
                Section::make('Additional Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('birthdate')
                                    ->label('Birthday')
                                    ->maxDate(now()),
                                TextInput::make('assistant_name')
                                    ->label('Assistant Name')
                                    ->maxLength(255),
                                TextInput::make('assistant_phone')
                                    ->label('Assistant Phone')
                                    ->tel()
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->maxLength(50),
                                TextInput::make('assistant_email')
                                    ->label('Assistant Email')
                                    ->email()
                                    ->maxLength(255),
                                TagsInput::make('segments')
                                    ->label('Segments')
                                    ->placeholder('Add segment')
                                    ->suggestions(config('contacts.segment_suggestions', []))
                                    ->columnSpanFull(),
                                Toggle::make('is_portal_user')
                                    ->label('Portal User')
                                    ->inline(false),
                                TextInput::make('portal_username')
                                    ->label('Portal Username')
                                    ->rules(['nullable', 'username'])
                                    ->maxLength(255),
                                Toggle::make('sync_enabled')
                                    ->label('Sync Enabled')
                                    ->inline(false),
                                TextInput::make('sync_reference')
                                    ->label('Sync Reference')
                                    ->maxLength(255),
                                Select::make('tags')
                                    ->label('Tags')
                                    ->relationship(
                                        'tags',
                                        'name',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                                            Auth::user()?->currentTeam,
                                            fn (Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey())
                                        )
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->maxLength(120),
                                        ColorPicker::make('color')->label('Color')->nullable(),
                                    ])
                                    ->createOptionAction(fn (Action $action): Action => $action->mutateFormDataUsing(
                                        fn (array $data): array => [
                                            ...$data,
                                            'team_id' => Auth::user()?->currentTeam?->getKey(),
                                        ]
                                    ))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                CustomFields::form()->build()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')->label('')->size(24)->circular(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->url(fn (People $record): ?string => $record->company_id ? CompanyResource::getUrl('view', [$record->company_id]) : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('job_title')
                    ->label('Job Title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('primary_email')
                    ->label(__('app.labels.email'))
                    ->state(fn (People $record): ?string => $record->primary_email)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone_mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('lead_source')
                    ->label('Lead Source')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_portal_user')
                    ->label('Portal User')
                    ->formatStateUsing(fn (mixed $state): string => $state ? 'Yes' : 'No')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('segments')
                    ->label('Segments')
                    ->formatStateUsing(fn (mixed $state): string => ArrayHelper::joinList($state) ?? '—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')
                    ->label('Tags')
                    ->getStateUsing(fn (People $record): string => ArrayHelper::joinList($record->tags->pluck('name')) ?? '—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (People $record): string => $record->created_by)
                    ->color(fn (People $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['tags', 'emails']))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('lead_source')
                    ->label('Lead Source')
                    ->options(
                        collect(config('contacts.lead_sources', []))
                            ->mapWithKeys(fn (string $source): array => [$source => $source])
                            ->all()
                    ),
                SelectFilter::make('is_portal_user')
                    ->label('Portal User')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                SelectFilter::make('tags')
                    ->label(__('app.labels.tags'))
                    ->relationship(
                        'tags',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                            Auth::user()?->currentTeam,
                            fn (Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey())
                        )
                    )
                    ->multiple()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(PeopleExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CasesRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
            SharedActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePeople::route('/create'),
            'edit' => EditPeople::route('/{record}/edit'),
            'view' => ViewPeople::route('/{record}'),
        ];
    }

    /**
     * @return Builder<People>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with('tags');
    }
}
