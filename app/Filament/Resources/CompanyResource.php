<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Data\AddressData;
use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\CreationSource;
use App\Enums\Industry;
use App\Filament\Exports\CompanyExporter;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Support\Filters\DateScopeFilter;
use App\Models\Company;
use App\Services\World\WorldDataService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Intervention\Validation\Rules\Postalcode;
use Relaticle\CustomFields\Facades\CustomFields;

final class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        $copyBillingToShipping = static function (Set $set, Get $get): void {
            $set('shipping_street', $get('billing_street'));
            $set('shipping_city_id', $get('billing_city_id'));
            $set('shipping_state_id', $get('billing_state_id'));
            $set('shipping_postal_code', $get('billing_postal_code'));
            $set('shipping_country_id', $get('billing_country_id'));
        };

        $teamMemberOptions = static function (?Company $record): array {
            $team = $record?->team ?? auth()->user()?->currentTeam;

            if ($team === null) {
                return [];
            }

            return $team->allUsers()
                ->sortBy('name')
                ->pluck('name', 'id')
                ->toArray();
        };

        $additionalAddressDefaults = static function (?Company $record): array {
            if (!$record instanceof \App\Models\Company) {
                return [];
            }

            return $record->addressCollection()
                ->reject(fn(AddressData $address): bool => in_array($address->type, [AddressType::BILLING, AddressType::SHIPPING], true))
                ->map(fn(AddressData $address): array => $address->toStorageArray())
                ->all();
        };

        return $schema
            ->components([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Company Profile')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->precognitive()
                                    ->columnSpan(2),
                                Select::make('account_owner_id')
                                    ->relationship('accountOwner', 'name')
                                    ->label(__('app.labels.account_owner'))
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpan(2),
                                Select::make('account_type')
                                    ->options(AccountType::options())
                                    ->enum(AccountType::class)
                                    ->label('Account Type')
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->columnSpan(2),
                                Select::make('industry')
                                    ->options(Industry::options())
                                    ->enum(Industry::class)
                                    ->label('Industry')
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->columnSpan(2),
                                Select::make('ownership')
                                    ->options(config('company.ownership_types'))
                                    ->label('Ownership')
                                    ->searchable()
                                    ->native(false)
                                    ->columnSpan(2),
                                Select::make('parent_company_id')
                                    ->relationship(
                                        'parentCompany',
                                        'name',
                                        fn(Builder $query, ?Company $record): Builder => $record instanceof \App\Models\Company
                                        ? $query->whereKeyNot($record->getKey())
                                        : $query
                                    )
                                    ->label('Parent Company')
                                    ->searchable()
                                    ->preload()
                                    ->rules([
                                        fn(?Company $record): \Closure => function (string $attribute, int|string|null $value, Closure $fail) use ($record): void {
                                            if ($value === null || !$record instanceof \App\Models\Company || $record->getKey() === null) {
                                                return;
                                            }

                                            if ((int) $value === $record->getKey()) {
                                                $fail('A company cannot be its own parent.');

                                                return;
                                            }

                                            if ($record->wouldCreateCycle((int) $value)) {
                                                $fail('Selecting this parent would create a cycle.');
                                            }
                                        },
                                    ])
                                    ->native(false)
                                    ->columnSpan(2),
                                Select::make('currency_code')
                                    ->options(config('company.currency_codes'))
                                    ->default(config('company.default_currency', 'USD'))
                                    ->label('Currency')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->columnSpan(12),
                        Section::make('Account Team')
                            ->schema([
                                Repeater::make('accountTeamMembers')
                                    ->relationship()
                                    ->label('Account Team')
                                    ->addActionLabel('Add teammate')
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $teamId = auth()->user()?->currentTeam?->getKey();

                                        return collect($data)
                                            ->map(fn(array $item): array => ['team_id' => $teamId] + $item)
                                            ->all();
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Company $record): array {
                                        $teamId = $record->team_id ?? auth()->user()?->currentTeam?->getKey();

                                        return collect($data)
                                            ->map(fn(array $item): array => ['team_id' => $teamId] + $item)
                                            ->all();
                                    })
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Team Member')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn(?Company $record): array => $teamMemberOptions($record))
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                        Select::make('role')
                                            ->label('Role')
                                            ->options(AccountTeamRole::options())
                                            ->default(AccountTeamRole::ACCOUNT_MANAGER->value)
                                            ->required()
                                            ->native(false),
                                        Select::make('access_level')
                                            ->label('Access')
                                            ->options(AccountTeamAccessLevel::options())
                                            ->default(AccountTeamAccessLevel::EDIT->value)
                                            ->required()
                                            ->native(false),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->helperText('Assign everyone collaborating on this account along with their responsibilities.'),
                            ])
                            ->columnSpan(12),
                        Section::make('Contact')
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(50)
                                    ->regex('/^[0-9+\\-\\s\\(\\)\\.]+$/')
                                    ->precognitive()
                                    ->columnSpan(2),
                                TextInput::make('primary_email')
                                    ->label('Primary Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->precognitive(debounce: 500)
                                    ->columnSpan(2),
                                TextInput::make('website')
                                    ->label('Website')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('social_links.linkedin')
                                    ->label('LinkedIn')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('social_links.twitter')
                                    ->label('Twitter')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->columnSpan(12),
                        Section::make('Addresses')
                            ->schema([
                                Toggle::make('copy_billing_to_shipping')
                                    ->label('Copy billing to shipping')
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function (bool $state, Set $set, Get $get) use ($copyBillingToShipping): void {
                                        if ($state) {
                                            $copyBillingToShipping($set, $get);
                                        }
                                    }),
                                Grid::make()
                                    ->columns(6)
                                    ->schema([
                                        TextInput::make('billing_street')
                                            ->label('Billing Street')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(string|int|null $state, Set $set, Get $get) => $get('copy_billing_to_shipping') ? $copyBillingToShipping($set, $get) : null),
                                        Select::make('billing_country_id')
                                            ->label('Billing Country')
                                            ->options(fn(WorldDataService $world) => $world->getCountries()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) use ($copyBillingToShipping) {
                                                $set('billing_state_id', null);
                                                $set('billing_city_id', null);
                                                if ($get('copy_billing_to_shipping')) {
                                                    $copyBillingToShipping($set, $get);
                                                }
                                            })
                                            ->required(),
                                        Select::make('billing_state_id')
                                            ->label('Billing State/Province')
                                            ->options(fn(Get $get, WorldDataService $world) => $get('billing_country_id') ? $world->getStates($get('billing_country_id'))->pluck('name', 'id') : [])
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) use ($copyBillingToShipping) {
                                                $set('billing_city_id', null);
                                                if ($get('copy_billing_to_shipping')) {
                                                    $copyBillingToShipping($set, $get);
                                                }
                                            })
                                            ->required(),
                                        Select::make('billing_city_id')
                                            ->label('Billing City')
                                            ->options(fn(Get $get, WorldDataService $world) => $get('billing_state_id') ? $world->getCities($get('billing_state_id'))->pluck('name', 'id') : [])
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn(string|int|null $state, Set $set, Get $get) => $get('copy_billing_to_shipping') ? $copyBillingToShipping($set, $get) : null)
                                            ->required(),
                                        TextInput::make('billing_postal_code')
                                            ->label('Billing Postal Code')
                                            ->live()
                                            ->maxLength(20)
                                            /*
                                            ->rules([
                                                'nullable',
                                                fn (Get $get, WorldDataService $world): Postalcode => new Postalcode([
                                                    strtolower((string) ($world->getCountry($get('billing_country_id'))?->iso2 ?? config('address.default_country', 'US'))),
                                                ]),
                                            ])
                                            */
                                            ->afterStateUpdated(fn(string|int|null $state, Set $set, Get $get) => $get('copy_billing_to_shipping') ? $copyBillingToShipping($set, $get) : null),
                                    ]),
                                Grid::make()
                                    ->columns(6)
                                    ->schema([
                                        TextInput::make('shipping_street')
                                            ->label('Shipping Street'),
                                        Select::make('shipping_country_id')
                                            ->label('Shipping Country')
                                            ->options(fn(WorldDataService $world) => $world->getCountries()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('shipping_state_id', null);
                                                $set('shipping_city_id', null);
                                            }),
                                        Select::make('shipping_state_id')
                                            ->label('Shipping State/Province')
                                            ->options(fn(Get $get, WorldDataService $world) => $get('shipping_country_id') ? $world->getStates($get('shipping_country_id'))->pluck('name', 'id') : [])
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('shipping_city_id', null)),
                                        Select::make('shipping_city_id')
                                            ->label('Shipping City')
                                            ->options(fn(Get $get, WorldDataService $world) => $get('shipping_state_id') ? $world->getCities($get('shipping_state_id'))->pluck('name', 'id') : [])
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('shipping_postal_code')
                                            ->label('Shipping Postal Code')
                                            ->maxLength(20)
                                        /*
                                        ->rules([
                                            'nullable',
                                            fn (Get $get, WorldDataService $world): Postalcode => new Postalcode([
                                                strtolower((string) ($world->getCountry($get('shipping_country_id'))?->iso2 ?? config('address.default_country', 'US'))),
                                            ]),
                                        ])
                                        */ ,
                                    ]),
                            ])
                            ->columnSpan(12),
                        Section::make('Additional Addresses')
                            ->schema([
                                Repeater::make('addresses')
                                    ->label('Addresses')
                                    ->addActionLabel('Add address')
                                    ->columns(6)
                                    ->default(fn(?Company $record): array => $additionalAddressDefaults($record))
                                    ->itemLabel(fn(array $state): string => $state['label'] ?? AddressType::tryFrom($state['type'] ?? '')?->label() ?? 'Address')
                                    ->schema([
                                        Select::make('type')
                                            ->label('Type')
                                            ->options(AddressType::options())
                                            ->required()
                                            ->native(false)
                                            ->columnSpan(2),
                                        TextInput::make('label')
                                            ->label('Label')
                                            ->maxLength(120)
                                            ->columnSpan(2),
                                        Select::make('country_code')
                                            ->label('Country')
                                            ->options(config('address.countries'))
                                            ->searchable()
                                            ->required()
                                            ->default(config('address.default_country', 'US'))
                                            ->native(false)
                                            ->columnSpan(2),
                                        TextInput::make('line1')
                                            ->label('Street')
                                            ->required()
                                            ->columnSpan(3),
                                        TextInput::make('line2')
                                            ->label('Street 2')
                                            ->columnSpan(3),
                                        TextInput::make('city')
                                            ->label('City')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('state')
                                            ->label('State/Province')
                                            ->columnSpan(2),
                                        TextInput::make('postal_code')
                                            ->label('Postal Code')
                                            ->maxLength(20)
                                            ->rules([
                                                'nullable',
                                                fn(Get $get): Postalcode => new Postalcode([
                                                    strtolower((string) ($get('country_code') ?? config('address.default_country', 'US'))),
                                                ]),
                                            ])
                                            ->columnSpan(2),
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90)
                                            ->columnSpan(2),
                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180)
                                            ->columnSpan(2),
                                    ])
                                    ->helperText('Add additional offices or mailing addresses beyond the primary billing and shipping locations.'),
                            ])
                            ->columnSpan(12),
                        Section::make('Financials')
                            ->schema([
                                TextInput::make('revenue')
                                    ->label('Annual Revenue')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->prefix(fn(Get $get): string => ($get('currency_code') ?? config('company.default_currency', 'USD')) . ' '),
                                TextInput::make('employee_count')
                                    ->label('Employees')
                                    ->integer()
                                    ->minValue(0),
                            ])
                            ->columns(2)
                            ->columnSpan(12),
                        Section::make('Details & Files')
                            ->schema([
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                SpatieMediaLibraryFileUpload::make('attachments')
                                    ->collection('attachments')
                                    ->multiple()
                                    ->preserveFilenames()
                                    ->appendFiles()
                                    ->downloadable()
                                    ->customProperties(fn(): array => [
                                        'uploaded_by' => auth()->id(),
                                    ])
                                    ->maxFiles(20)
                                    ->columnSpanFull()
                                    ->helperText('Upload contracts, proposals, or supporting documents.'),
                                \App\Filament\Forms\Components\UnsplashPicker::make('unsplash_image')
                                    ->label('Unsplash Image')
                                    ->imageSize('regular')
                                    ->columnSpanFull()
                                    ->afterStateUpdated(function ($state, Company $record): void {
                                        if (!$state) {
                                            return;
                                        }
                                        // Ideally we resolve the UnsplashService and handle logic here
                                        // But the picker might already return the ID.
                                        // For now, we assume the picker works as documented in docs/unsplash-integration.md
                                        // Logic to be refined if needed.
                                    }),
                            ])
                            ->columnSpan(12),
                        Section::make('Labels & Tags')
                            ->schema([
                                Select::make('tags')
                                    ->label('Tags')
                                    ->relationship(
                                        'tags',
                                        'name',
                                        modifyQueryUsing: fn(Builder $query): Builder => $query->when(
                                            Auth::user()?->currentTeam,
                                            fn(Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey())
                                        )
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->maxLength(120),
                                        ColorPicker::make('color')->label('Color')->nullable(),
                                    ])
                                    ->createOptionAction(fn(Action $action): Action => $action->mutateFormDataUsing(
                                        fn(array $data): array => [
                                            ...$data,
                                            'team_id' => Auth::user()?->currentTeam?->getKey(),
                                        ]
                                    )),
                            ])
                            ->columnSpan(12),
                    ]),
                CustomFields::form()->forSchema($schema)->build()->columns(1),
            ])
            ->columns(1)
            ->inlineLabel();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->label('')->imageSize(28)->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parentCompany.name')
                    ->label('Parent')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('account_type')
                    ->label('Type')
                    ->formatStateUsing(fn(?AccountType $state): string => $state?->label() ?? '—')
                    ->badge()
                    ->color(fn(?AccountType $state): string => $state?->color() ?? 'gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accountOwner.name')
                    ->label(__('app.labels.account_owner'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('account_team_members_count')
                    ->label('Team')
                    ->counts('accountTeamMembers')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn(int $state): string => $state . ' member' . ($state === 1 ? '' : 's')),
                TextColumn::make('ownership')
                    ->label('Ownership')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('website')
                    ->label('Website')
                    ->url(fn(Company $record): ?string => $record->website ?: null)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('industry')
                    ->label('Industry')
                    ->formatStateUsing(fn(?Industry $state): string => $state?->label() ?? '—')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('billing_city')
                    ->label('Billing City')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_count')
                    ->label('Employees')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revenue')
                    ->label('Annual Revenue')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function (Company $record): string {
                        $latest = $record->latestAnnualRevenue;

                        if ($latest !== null) {
                            return ($latest->currency_code ?? $record->currency_code ?? 'USD') . ' ' . number_format((float) $latest->amount, 2) . ' (' . $latest->year . ')';
                        }

                        if ($record->revenue !== null) {
                            return ($record->currency_code ?? 'USD') . ' ' . number_format((float) $record->revenue, 2);
                        }

                        return '—';
                    }),
                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('child_companies_count')
                    ->label('Children')
                    ->counts('childCompanies')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn(Company $record): string => $record->created_by)
                    ->color(fn(Company $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.creation_date'))
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.last_update'))
                    ->since()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DateScopeFilter::make(),
                SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->options(AccountType::options())
                    ->multiple(),
                SelectFilter::make('industry')
                    ->label('Industry')
                    ->options(Industry::options())
                    ->multiple(),
                Filter::make('employee_count')
                    ->label('Employee Count')
                    ->form([
                        TextInput::make('min_employee_count')
                            ->label('Min employees')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('max_employee_count')
                            ->label('Max employees')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['min_employee_count'] ?? null)) {
                            $indicators[] = 'Min ' . number_format((int) $data['min_employee_count']);
                        }

                        if (filled($data['max_employee_count'] ?? null)) {
                            $indicators[] = 'Max ' . number_format((int) $data['max_employee_count']);
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $minEmployees = filled($data['min_employee_count'] ?? null) ? (int) $data['min_employee_count'] : null;
                        $maxEmployees = filled($data['max_employee_count'] ?? null) ? (int) $data['max_employee_count'] : null;

                        return $query->employeeCountBetween($minEmployees, $maxEmployees);
                    }),
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(CompanyExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    /**
     * @return Builder<Company>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'latestAnnualRevenue',
                'creator:id,name,avatar',
                'accountOwner:id,name,avatar',
                'parentCompany:id,name',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
