<?php

declare(strict_types=1);

namespace App\Filament\Resources\Accounts\Schemas;

use App\Data\AddressData;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Account;
use Closure;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Validation\Rules\Postalcode;

final class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        $copyBillingToShipping = static function (Set $set, Get $get): void {
            $addresses = collect($get('addresses') ?? []);
            $billing = $addresses->first(fn (array $address): bool => ($address['type'] ?? null) === AddressType::BILLING->value);

            if ($billing === null) {
                return;
            }

            $shippingIndex = $addresses->search(fn (array $address): bool => ($address['type'] ?? null) === AddressType::SHIPPING->value);
            $shipping = [...$billing, 'type' => AddressType::SHIPPING->value];

            if ($shippingIndex !== false && $shippingIndex !== null) {
                $addresses[$shippingIndex] = $shipping;
            } else {
                $addresses->push($shipping);
            }

            $set('addresses', $addresses->values()->all());
        };

        $addressDefaults = static function (?Account $record): array {
            if ($record instanceof \App\Models\Account) {
                $existing = $record->addressCollection()
                    ->map(fn (AddressData $address): array => $address->toStorageArray())
                    ->all();

                if ($existing !== []) {
                    return $existing;
                }
            }

            $defaultCountry = config('address.default_country', 'US');

            return [
                [
                    'type' => AddressType::BILLING->value,
                    'line1' => '',
                    'city' => '',
                    'country_code' => $defaultCountry,
                ],
                [
                    'type' => AddressType::SHIPPING->value,
                    'line1' => '',
                    'city' => '',
                    'country_code' => $defaultCountry,
                ],
            ];
        };

        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options(AccountType::options())
                            ->enum(AccountType::class)
                            ->label('Account Type')
                            ->native(false)
                            ->required(),
                        Select::make('industry')
                            ->options(Industry::options())
                            ->enum(Industry::class)
                            ->label('Industry')
                            ->searchable()
                            ->native(false)
                            ->required(),
                        TextInput::make('annual_revenue')
                            ->label('Annual Revenue')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix(fn (Get $get): string => ($get('currency') ?? config('company.default_currency', 'USD')) . ' '),
                        TextInput::make('employee_count')
                            ->label('Employees')
                            ->integer()
                            ->minValue(0),
                        Select::make('currency')
                            ->options(config('company.currency_codes'))
                            ->label('Currency')
                            ->default(config('company.default_currency'))
                            ->native(false),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->label('Owner')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),
                        Select::make('assigned_to_id')
                            ->relationship('assignedTo', 'name')
                            ->label('Assigned To')
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('parent_id')
                            ->relationship(
                                'parent',
                                'name',
                                fn (Builder $query, ?Account $record): Builder => $record instanceof \App\Models\Account
                                    ? $query->whereKeyNot($record->getKey())
                                    : $query,
                            )
                            ->label('Parent Account')
                            ->searchable()
                            ->preload()
                            ->rules([
                                fn (?Account $record): \Closure => function (string $attribute, int|string|null $value, Closure $fail) use ($record): void {
                                    if ($value === null || ! $record instanceof \App\Models\Account || $record->getKey() === null) {
                                        return;
                                    }

                                    if ((int) $value === $record->getKey()) {
                                        $fail('An account cannot be its own parent.');

                                        return;
                                    }

                                    if ($record->wouldCreateCycle((int) $value)) {
                                        $fail('Selecting this parent would create a cycle.');
                                    }
                                },
                            ])
                            ->native(false),
                    ])
                    ->columns(3),
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
                        Repeater::make('addresses')
                            ->label('Addresses')
                            ->addActionLabel('Add address')
                            ->columns(6)
                            ->minItems(1)
                            ->default(fn (?Account $record): array => $addressDefaults($record))
                            ->itemLabel(fn (array $state): string => $state['label'] ?? AddressType::tryFrom($state['type'] ?? '')?->label() ?? 'Address')
                            ->afterStateUpdated(fn ($state, Set $set, Get $get) => $get('copy_billing_to_shipping') ? $copyBillingToShipping($set, $get) : null)
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
                                        fn (Get $get): Postalcode => new Postalcode([
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
                            ]),
                    ])
                    ->columns(1),
                Section::make('Web & Social')
                    ->schema([
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
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
                    ])
                    ->columns(2),
                Section::make('Additional Details')
                    ->schema([
                        KeyValue::make('custom_fields')
                            ->label('Custom Fields')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Field')
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->downloadable()
                            ->appendFiles()
                            ->preserveFilenames()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
