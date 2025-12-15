<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportCaseResource\Forms;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Facades\CustomFields;

final class SupportCaseForm
{
    /**
     * @param array<string> $excludeFields
     */
    public static function get(Schema $schema, array $excludeFields = []): Schema
    {
        $queues = [
            'general' => 'General',
            'billing' => 'Billing',
            'technical' => 'Technical Support',
            'product' => 'Product',
        ];

        $components = [
            MinimalTabs::make('Case')
                ->tabs([
                    MinimalTabs\Tab::make(__('app.labels.details'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Case Details')
                                ->schema([
                                    TextInput::make('case_number')
                                        ->label(__('app.labels.case_number'))
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn (?object $record): bool => $record !== null),
                                    TextInput::make('subject')
                                        ->label(__('app.labels.title'))
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Textarea::make('description')
                                        ->label(__('app.labels.description'))
                                        ->rows(4)
                                        ->columnSpanFull(),
                                    Select::make('status')
                                        ->label(__('app.labels.status'))
                                        ->options(CaseStatus::class)
                                        ->default(CaseStatus::NEW)
                                        ->required(),
                                    Select::make('priority')
                                        ->label(__('app.labels.priority'))
                                        ->options(CasePriority::class)
                                        ->default(CasePriority::P3)
                                        ->required(),
                                    Select::make('type')
                                        ->label(__('app.labels.type'))
                                        ->options(CaseType::class)
                                        ->default(CaseType::QUESTION)
                                        ->required(),
                                    Select::make('channel')
                                        ->label(__('app.labels.channel'))
                                        ->options(CaseChannel::class)
                                        ->default(CaseChannel::INTERNAL)
                                        ->required(),
                                    Select::make('queue')
                                        ->label(__('app.labels.queue'))
                                        ->options($queues)
                                        ->searchable()
                                        ->nullable(),
                                ])
                                ->columns(2),
                        ]),
                    MinimalTabs\Tab::make(__('app.labels.assignments'))
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Section::make('Assignments')
                                ->schema(array_values(array_filter([
                                    in_array('company_id', $excludeFields, true) ? null : Select::make('company_id')
                                        ->label(__('app.labels.company'))
                                        ->relationship('company', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(2),
                                    in_array('contact_id', $excludeFields, true) ? null : Select::make('contact_id')
                                        ->label(__('app.labels.contact_person'))
                                        ->relationship(
                                            'contact',
                                            'name',
                                            fn (Builder $query, Get $get): Builder => $query->when(
                                                $get('company_id'),
                                                fn (Builder $builder, int|string|null $companyId): Builder => $builder->where('company_id', $companyId),
                                            ),
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(2),
                                    Select::make('assigned_to_id')
                                        ->label(__('app.labels.assignee'))
                                        ->relationship('assignee', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(2),
                                    Select::make('assigned_team_id')
                                        ->label(__('app.labels.assigned_team'))
                                        ->relationship('assignedTeam', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(2),
                                ])))
                                ->columns(2),
                        ]),
                    MinimalTabs\Tab::make(__('app.labels.sla_resolution'))
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Section::make('SLA & Resolution')
                                ->schema([
                                    DateTimePicker::make('sla_due_at')
                                        ->label(__('app.labels.sla_due_at'))
                                        ->seconds(false),
                                    DateTimePicker::make('first_response_at')
                                        ->label(__('app.labels.first_response_at'))
                                        ->seconds(false),
                                    DateTimePicker::make('escalated_at')
                                        ->label(__('app.labels.escalated_at'))
                                        ->seconds(false),
                                    DateTimePicker::make('resolved_at')
                                        ->label(__('app.labels.resolved_at'))
                                        ->seconds(false),
                                    Textarea::make('resolution_summary')
                                        ->label(__('app.labels.resolution'))
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                    MinimalTabs\Tab::make(__('app.labels.integrations'))
                        ->icon('heroicon-o-link')
                        ->schema([
                            Section::make('Threading & Integrations')
                                ->schema([
                                    TextInput::make('thread_reference')
                                        ->label(__('app.labels.thread_reference'))
                                        ->maxLength(255),
                                    TextInput::make('email_message_id')
                                        ->label(__('app.labels.email_message_id'))
                                        ->maxLength(255),
                                    TextInput::make('customer_portal_url')
                                        ->label(__('app.labels.customer_portal_url'))
                                        ->url()
                                        ->maxLength(255),
                                    TextInput::make('knowledge_base_reference')
                                        ->label(__('app.labels.knowledge_base_reference'))
                                        ->maxLength(255),
                                ])
                                ->columns(2),
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
        ];

        return $schema
            ->components($components)
            ->columns(1);
    }
}
