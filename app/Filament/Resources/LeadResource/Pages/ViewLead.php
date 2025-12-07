<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\OpportunityResource;
use App\Models\Lead;
use App\Services\LeadConversionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Relaticle\CustomFields\Facades\CustomFields;

final class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('convert')
                ->label('Convert to Deal')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->modalWidth(Width::ExtraLarge)
                ->visible(fn (Lead $record): bool => ! $record->isConverted())
                ->form(fn (Lead $record): array => [
                    Select::make('company_id')
                        ->label('Existing Company')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                    TextInput::make('new_company_name')
                        ->label('Create Company')
                        ->maxLength(255)
                        ->default($record->company_name ?? $record->name)
                        ->columnSpanFull(),
                    Toggle::make('create_contact')
                        ->label('Create Contact')
                        ->default(true),
                    TextInput::make('contact_name')
                        ->label('Contact Name')
                        ->maxLength(255)
                        ->default($record->name)
                        ->required(fn (Get $get): bool => (bool) $get('create_contact'))
                        ->visible(fn (Get $get): bool => (bool) $get('create_contact')),
                    TextInput::make('contact_email')
                        ->label('Contact Email')
                        ->email()
                        ->maxLength(255)
                        ->default($record->email)
                        ->visible(fn (Get $get): bool => (bool) $get('create_contact')),
                    TextInput::make('contact_phone')
                        ->label('Contact Phone')
                        ->maxLength(50)
                        ->default($record->mobile ?? $record->phone)
                        ->visible(fn (Get $get): bool => (bool) $get('create_contact')),
                    Toggle::make('create_opportunity')
                        ->label('Create Deal')
                        ->default(true)
                        ->columnSpanFull(),
                    TextInput::make('opportunity_name')
                        ->label('Deal Name')
                        ->maxLength(255)
                        ->default($record->name)
                        ->required(fn (Get $get): bool => (bool) $get('create_opportunity'))
                        ->visible(fn (Get $get): bool => (bool) $get('create_opportunity')),
                    TextInput::make('amount')
                        ->label('Value')
                        ->numeric()
                        ->suffix('currency')
                        ->visible(fn (Get $get): bool => (bool) $get('create_opportunity')),
                    TextInput::make('probability')
                        ->label('Probability (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->visible(fn (Get $get): bool => (bool) $get('create_opportunity')),
                    DatePicker::make('close_date')
                        ->label('Expected Close Date')
                        ->native(false)
                        ->visible(fn (Get $get): bool => (bool) $get('create_opportunity')),
                    Select::make('stage_option_id')
                        ->label('Pipeline Stage')
                        ->options($this->stageOptions())
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => (bool) $get('create_opportunity')),
                ])
                ->action(function (LeadConversionService $service, array $data, Lead $record): void {
                    $result = $service->convert($record, $data);

                    Notification::make()
                        ->title('Lead converted successfully')
                        ->success()
                        ->send();

                    if ($result->opportunity instanceof \App\Models\Opportunity) {
                        $this->redirect(OpportunityResource::getUrl('view', [$result->opportunity]));

                        return;
                    }

                    $this->redirect(LeadResource::getUrl('view', [$record]));
                }),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    TextEntry::make('name')
                        ->label('')
                        ->size(TextSize::Large)
                        ->grow(true),
                    TextEntry::make('status')
                        ->label(__('app.labels.status'))
                        ->badge()
                        ->color(fn (Lead $record): string => $record->status?->color() ?? 'secondary')
                        ->formatStateUsing(fn (Lead $record): string => $record->status?->getLabel() ?? ''),
                    TextEntry::make('source')
                        ->label(__('app.labels.source'))
                        ->badge()
                        ->formatStateUsing(fn (Lead $record): string => $record->source?->getLabel() ?? ''),
                    TextEntry::make('score')
                        ->label(__('app.labels.score'))
                        ->grow(false),
                ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('job_title')
                            ->label('Job Title'),
                        TextEntry::make('company_name')
                            ->label(__('app.labels.company')),
                        TextEntry::make('company.name')
                            ->label('Linked Account')
                            ->color('primary')
                            ->url(fn (Lead $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                        TextEntry::make('email')
                            ->label(__('app.labels.email'))
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label(__('app.labels.phone'))
                            ->copyable(),
                        TextEntry::make('mobile')
                            ->label('Mobile')
                            ->copyable(),
                        TextEntry::make('website')
                            ->label(__('app.labels.website'))
                            ->copyable(),
                        TextEntry::make('assignedTo.name')
                            ->label(__('app.labels.assignee')),
                        TextEntry::make('assignment_strategy')
                            ->label(__('app.labels.assignment_strategy'))
                            ->formatStateUsing(fn (Lead $record): string => $record->assignment_strategy?->getLabel() ?? (string) $record->assignment_strategy),
                        TextEntry::make('grade')
                            ->label(__('app.labels.grade'))
                            ->badge()
                            ->color(fn (Lead $record): string => $record->grade?->color() ?? 'secondary')
                            ->formatStateUsing(fn (Lead $record): string => $record->grade?->getLabel() ?? ''),
                        TextEntry::make('territory')
                            ->label(__('app.labels.territory')),
                        TextEntry::make('nurture_status')
                            ->label(__('app.labels.nurture_status'))
                            ->badge()
                            ->color(fn (Lead $record): string => $record->nurture_status?->color() ?? 'secondary')
                            ->formatStateUsing(fn (Lead $record): string => $record->nurture_status?->getLabel() ?? ''),
                        TextEntry::make('nurture_program')
                            ->label('Program'),
                        TextEntry::make('next_nurture_touch_at')
                            ->label('Next Nurture Touch')
                            ->dateTime(),
                    ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('qualified_at')
                            ->label('Qualified At')
                            ->dateTime(),
                        TextEntry::make('qualifiedBy.name')
                            ->label('Qualified By'),
                        TextEntry::make('converted_at')
                            ->label('Converted At')
                            ->dateTime(),
                        TextEntry::make('convertedBy.name')
                            ->label('Converted By'),
                        TextEntry::make('convertedCompany.name')
                            ->label('Converted Company'),
                        TextEntry::make('convertedContact.name')
                            ->label('Converted Contact'),
                        TextEntry::make('convertedOpportunity.name')
                            ->label('Converted Opportunity'),
                    ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('duplicate_score')
                            ->label('Duplicate Confidence'),
                        TextEntry::make('duplicateOf.name')
                            ->label('Marked Duplicate Of'),
                        TextEntry::make('web_form_key')
                            ->label('Web Form Key'),
                        TextEntry::make('web_form_payload')
                            ->label('Web Form Payload')
                            ->columnSpanFull()
                            ->formatStateUsing(fn (mixed $state): string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                            ->wrap(),
                    ]),
                TextEntry::make('qualification_notes')
                    ->label('Qualification Notes')
                    ->columnSpanFull()
                    ->wrap(),
                CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
                RepeatableEntry::make('activity_timeline')
                    ->label('Activity')
                    ->columnSpanFull()
                    ->state(fn (Lead $record): array => $record->getActivityTimeline()
                        ->map(fn (array $item): array => [
                            'title' => $item['title'],
                            'summary' => $item['summary'],
                            'type' => ucfirst((string) ($item['type'] ?? '')),
                            'created_at' => $item['created_at'],
                        ])
                        ->all())
                    ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Entry')
                            ->columnSpan(4),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->columnSpan(2),
                        TextEntry::make('summary')
                            ->label('Summary')
                            ->columnSpan(4),
                        TextEntry::make('created_at')
                            ->label('When')
                            ->since()
                            ->columnSpan(2),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int|string, string>
     */
    private function stageOptions(): array
    {
        return app(LeadConversionService::class)->stageOptions();
    }
}
