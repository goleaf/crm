<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportCaseResource\Pages;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Filament\Resources\SupportCaseResource;
use App\Models\SupportCase;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Facades\CustomFields;

final class ViewSupportCase extends ViewRecord
{
    protected static string $resource = SupportCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    TextEntry::make('case_number')
                        ->label(__('app.labels.case_number'))
                        ->weight('medium'),
                    TextEntry::make('status')
                        ->label(__('app.labels.status'))
                        ->badge()
                        ->color(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getColor() : (CaseStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                        ->formatStateUsing(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getLabel() : (CaseStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                    TextEntry::make('priority')
                        ->label(__('app.labels.priority'))
                        ->badge()
                        ->color(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getColor() : (CasePriority::tryFrom((string) $state)?->getColor() ?? 'gray'))
                        ->formatStateUsing(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getLabel() : (CasePriority::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                    TextEntry::make('type')
                        ->label(__('app.labels.type'))
                        ->badge()
                        ->formatStateUsing(fn (CaseType|string|null $state): string => $state instanceof CaseType ? $state->getLabel() : (CaseType::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                    TextEntry::make('channel')
                        ->label(__('app.labels.channel'))
                        ->badge()
                        ->color('gray')
                        ->formatStateUsing(fn (CaseChannel|string|null $state): string => $state instanceof CaseChannel ? $state->getLabel() : (CaseChannel::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                ]),
                TextEntry::make('subject')
                    ->label(__('app.labels.title'))
                    ->size(TextSize::Large)
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->label(__('app.labels.description'))
                    ->markdown()
                    ->columnSpanFull(),
                Flex::make([
                    TextEntry::make('company.name')
                        ->label(__('app.labels.company'))
                        ->color('primary')
                        ->url(fn (SupportCase $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                    TextEntry::make('contact.name')
                        ->label(__('app.labels.contact_person'))
                        ->color('primary')
                        ->url(fn (SupportCase $record): ?string => $record->contact ? PeopleResource::getUrl('view', [$record->contact]) : null),
                    TextEntry::make('assignee.name')
                        ->label(__('app.labels.assignee')),
                    TextEntry::make('assignedTeam.name')
                        ->label(__('app.labels.assigned_team')),
                ]),
                Flex::make([
                    TextEntry::make('thread_reference')
                        ->label(__('app.labels.thread_reference')),
                    TextEntry::make('email_message_id')
                        ->label(__('app.labels.email_message_id')),
                    TextEntry::make('customer_portal_url')
                        ->label(__('app.labels.customer_portal_url'))
                        ->color('primary')
                        ->url(fn (SupportCase $record): ?string => $record->customer_portal_url),
                    TextEntry::make('knowledge_base_reference')
                        ->label(__('app.labels.knowledge_base_reference')),
                ]),
                Flex::make([
                    TextEntry::make('sla_due_at')
                        ->label(__('app.labels.sla_due_at'))
                        ->dateTime(),
                    TextEntry::make('first_response_at')
                        ->label(__('app.labels.first_response_at'))
                        ->dateTime(),
                    TextEntry::make('escalated_at')
                        ->label(__('app.labels.escalated_at'))
                        ->dateTime(),
                    TextEntry::make('resolved_at')
                        ->label(__('app.labels.resolved_at'))
                        ->dateTime(),
                ]),
                TextEntry::make('resolution_summary')
                    ->label(__('app.labels.resolution'))
                    ->markdown()
                    ->columnSpanFull(),
                CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }
}
