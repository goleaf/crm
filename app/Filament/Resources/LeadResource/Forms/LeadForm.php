<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Forms;

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Components\MinimalTabs;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Facades\CustomFields;

final class LeadForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                MinimalTabs::make('Lead')
                    ->tabs([
                        MinimalTabs\Tab::make(__('app.labels.profile'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make()
                                    ->columns(12)
                                    ->schema([
                                        Section::make('Lead Profile')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('app.labels.name'))
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                TextInput::make('job_title')
                                                    ->label('Job Title')
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                TextInput::make('company_name')
                                                    ->label('Company (as provided)')
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                Select::make('company_id')
                                                    ->label('Link to Account')
                                                    ->relationship('company', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2),
                                                TextInput::make('email')
                                                    ->label(__('app.labels.email'))
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                TextInput::make('phone')
                                                    ->label(__('app.labels.phone'))
                                                    ->tel()
                                                    ->maxLength(50)
                                                    ->columnSpan(2),
                                                TextInput::make('mobile')
                                                    ->label('Mobile')
                                                    ->tel()
                                                    ->maxLength(50)
                                                    ->columnSpan(2),
                                                TextInput::make('website')
                                                    ->label(__('app.labels.website'))
                                                    ->url()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                Textarea::make('description')
                                                    ->label(__('app.labels.description'))
                                                    ->rows(4)
                                                    ->columnSpan(6),
                                            ])
                                            ->columns(6)
                                            ->columnSpan(8),
                                        Section::make('Status & Routing')
                                            ->schema([
                                                Select::make('status')
                                                    ->options(LeadStatus::options())
                                                    ->default(LeadStatus::NEW)
                                                    ->native(false)
                                                    ->required(),
                                                Select::make('source')
                                                    ->options(LeadSource::options())
                                                    ->default(LeadSource::WEBSITE)
                                                    ->native(false)
                                                    ->required(),
                                                Select::make('lead_type')
                                                    ->label(__('app.labels.lead_type'))
                                                    ->options(LeadType::options())
                                                    ->native(false),
                                                TextInput::make('lead_value')
                                                    ->label(__('app.labels.lead_value'))
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->prefix('$'),
                                                DatePicker::make('expected_close_date')
                                                    ->label(__('app.labels.expected_close_date'))
                                                    ->native(false),
                                                TextInput::make('score')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(1000)
                                                    ->suffix('pts'),
                                                Select::make('grade')
                                                    ->options(LeadGrade::options())
                                                    ->native(false)
                                                    ->placeholder('Select grade'),
                                                Select::make('assignment_strategy')
                                                    ->label('Assignment')
                                                    ->options(LeadAssignmentStrategy::options())
                                                    ->default(LeadAssignmentStrategy::MANUAL)
                                                    ->native(false),
                                                Select::make('assigned_to_id')
                                                    ->label('Assigned To')
                                                    ->relationship('assignedTo', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                                TextInput::make('territory')
                                                    ->label('Territory')
                                                    ->maxLength(255),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(4),
                                    ]),
                            ]),
                        MinimalTabs\Tab::make(__('app.labels.nurturing'))
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Nurturing')
                                    ->schema([
                                        Select::make('nurture_status')
                                            ->options(LeadNurtureStatus::options())
                                            ->default(LeadNurtureStatus::NOT_STARTED)
                                            ->native(false),
                                        TextInput::make('nurture_program')
                                            ->label('Program / Workflow')
                                            ->maxLength(255),
                                        DateTimePicker::make('next_nurture_touch_at')
                                            ->label('Next Nurture Touch'),
                                    ])
                                    ->columns(2)
                                    ->columnSpan(6),
                            ]),
                        MinimalTabs\Tab::make(__('app.labels.qualification'))
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Section::make('Qualification & Conversion')
                                    ->schema([
                                        DateTimePicker::make('qualified_at')
                                            ->label('Qualified At'),
                                        Select::make('qualified_by_id')
                                            ->label('Qualified By')
                                            ->relationship('qualifiedBy', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Textarea::make('qualification_notes')
                                            ->label('Qualification Notes')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        DateTimePicker::make('converted_at')
                                            ->label('Converted At'),
                                        Select::make('converted_by_id')
                                            ->label('Converted By')
                                            ->relationship('convertedBy', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Select::make('converted_company_id')
                                            ->label('Converted Company')
                                            ->relationship('convertedCompany', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Select::make('converted_contact_id')
                                            ->label('Converted Contact')
                                            ->relationship('convertedContact', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Select::make('converted_opportunity_id')
                                            ->label('Converted Opportunity')
                                            ->relationship('convertedOpportunity', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columns(2)
                                    ->columnSpan(6),
                            ]),
                        MinimalTabs\Tab::make(__('app.labels.data_quality'))
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Data Quality')
                                    ->schema([
                                        Select::make('duplicate_of_id')
                                            ->label('Marked Duplicate Of')
                                            ->relationship('duplicateOf', 'name')
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('duplicate_score')
                                            ->label('Duplicate Confidence')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01),
                                        TextInput::make('web_form_key')
                                            ->label('Web Form Key')
                                            ->maxLength(255),
                                        Textarea::make('web_form_payload')
                                            ->label('Web Form Payload')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpan(6),
                            ]),
                        MinimalTabs\Tab::make(__('app.labels.tags'))
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Section::make('Labels & Tags')
                                    ->schema([
                                        Select::make('tags')
                                            ->label('Tags')
                                            ->relationship(
                                                'tags',
                                                'name',
                                                modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                                                    Auth::user()?->currentTeam,
                                                    fn (Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey()),
                                                ),
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
                                                ],
                                            )),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        MinimalTabs\Tab::make(__('app.labels.custom_fields'))
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->visible(fn () => CustomFields::form()->forSchema($schema)->hasFields())
                            ->schema([
                                CustomFields::form()->forSchema($schema)->build()->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }
}
