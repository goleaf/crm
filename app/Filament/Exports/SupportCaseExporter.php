<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\SupportCase;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Relaticle\CustomFields\Facades\CustomFields;

final class SupportCaseExporter extends BaseExporter
{
    protected static ?string $model = SupportCase::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('app.labels.id')),
            ExportColumn::make('case_number')
                ->label(__('app.labels.case_number')),
            ExportColumn::make('subject')
                ->label(__('app.labels.title')),
            ExportColumn::make('status')
                ->label(__('app.labels.status')),
            ExportColumn::make('priority')
                ->label(__('app.labels.priority')),
            ExportColumn::make('type')
                ->label(__('app.labels.type')),
            ExportColumn::make('channel')
                ->label(__('app.labels.channel')),
            ExportColumn::make('queue')
                ->label(__('app.labels.queue')),
            ExportColumn::make('team.name')
                ->label(__('app.labels.team')),
            ExportColumn::make('assignedTeam.name')
                ->label(__('app.labels.assigned_team')),
            ExportColumn::make('assignee.name')
                ->label(__('app.labels.assignee')),
            ExportColumn::make('thread_reference')
                ->label(__('app.labels.thread_reference')),
            ExportColumn::make('email_message_id')
                ->label(__('app.labels.email_message_id')),
            ExportColumn::make('customer_portal_url')
                ->label(__('app.labels.customer_portal_url')),
            ExportColumn::make('knowledge_base_reference')
                ->label(__('app.labels.knowledge_base_reference')),
            ExportColumn::make('company.name')
                ->label(__('app.labels.company')),
            ExportColumn::make('contact.name')
                ->label(__('app.labels.contact_person')),
            ExportColumn::make('creation_source')
                ->label(__('app.labels.creation_source'))
                ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state),
            ExportColumn::make('sla_due_at')
                ->label(__('app.labels.sla_due_at'))
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('first_response_at')
                ->label(__('app.labels.first_response_at'))
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('resolved_at')
                ->label(__('app.labels.resolved_at'))
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('created_at')
                ->label(__('app.labels.created_at'))
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('updated_at')
                ->label(__('app.labels.updated_at'))
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('deleted_at')
                ->label(__('app.labels.deleted_at'))
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('notes_count')
                ->label(__('app.labels.number_of_notes'))
                ->state(fn (SupportCase $case): int => $case->notes()->count()),
            ExportColumn::make('tasks_count')
                ->label(__('app.labels.number_of_tasks'))
                ->state(fn (SupportCase $case): int => $case->tasks()->count()),

            ...CustomFields::exporter()->forModel(self::getModel())->columns(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your case export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
