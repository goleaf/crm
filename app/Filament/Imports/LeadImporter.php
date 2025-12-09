<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Enums\CreationSource;
use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Enum;
use Relaticle\CustomFields\Facades\CustomFields;

final class LeadImporter extends BaseImporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Jane Smith')
                ->fillRecordUsing(function (Lead $record, string $state, Importer $importer): void {
                    $record->name = $state;

                    if (! $record->exists) {
                        $record->team_id = $importer->import->team_id;
                        $record->creator_id = $importer->import->user_id;
                        $record->creation_source = CreationSource::IMPORT;
                        $record->import_id = $importer->import->getKey();
                    }
                }),
            ImportColumn::make('email')
                ->guess(['email', 'primary_email'])
                ->rules(['nullable', 'email:rfc,dns', 'max:255'])
                ->example('lead@example.com')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->email = $state ? strtolower(trim($state)) : null;
                }),
            ImportColumn::make('phone')
                ->guess(['phone', 'phone_number'])
                ->rules(['nullable', 'string', 'max:50'])
                ->example('+1 555 123 4567')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->phone = $state ? trim($state) : null;
                }),
            ImportColumn::make('company_name')
                ->label('Company')
                ->guess(['company', 'company_name'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Acme Corp')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->company_name = $state ? trim($state) : null;
                }),
            ImportColumn::make('description')
                ->rules(['nullable', 'string'])
                ->example('Interested in a product demo')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->description = $state ? trim($state) : $record->description;
                }),
            ImportColumn::make('lead_value')
                ->label('Lead Value')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('15000')
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $record->lead_value = is_numeric($state) ? (float) $state : $record->lead_value;
                }),
            ImportColumn::make('lead_type')
                ->label('Lead Type')
                ->rules(['nullable', new Enum(LeadType::class)])
                ->options(LeadType::options())
                ->example(LeadType::NEW_BUSINESS->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $type = $state instanceof LeadType ? $state : (is_string($state) ? LeadType::tryFrom($state) : null);
                    $record->lead_type = $type ?? $record->lead_type;
                }),
            ImportColumn::make('expected_close_date')
                ->label('Expected Close Date')
                ->rules(['nullable', 'date'])
                ->example(now()->addMonth()->toDateString())
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->expected_close_date = $state ?: $record->expected_close_date;
                }),
            ImportColumn::make('status')
                ->rules(['nullable', new Enum(LeadStatus::class)])
                ->options(LeadStatus::options())
                ->example(LeadStatus::WORKING->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $status = $state instanceof LeadStatus ? $state : (is_string($state) ? LeadStatus::tryFrom($state) : null);
                    $record->status = $status ?? $record->status;
                }),
            ImportColumn::make('source')
                ->rules(['nullable', new Enum(LeadSource::class)])
                ->options(LeadSource::options())
                ->example(LeadSource::WEB_FORM->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $source = $state instanceof LeadSource ? $state : (is_string($state) ? LeadSource::tryFrom($state) : null);
                    $record->source = $source ?? $record->source;
                }),
            ImportColumn::make('grade')
                ->rules(['nullable', new Enum(LeadGrade::class)])
                ->options(LeadGrade::options())
                ->example(LeadGrade::B->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $grade = $state instanceof LeadGrade ? $state : (is_string($state) ? LeadGrade::tryFrom($state) : null);
                    $record->grade = $grade ?? $record->grade;
                }),
            ImportColumn::make('score')
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('75')
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $record->score = is_numeric($state) ? (int) $state : ($record->score ?? 0);
                }),
            ImportColumn::make('assignment_strategy')
                ->label('Assignment')
                ->rules(['nullable', new Enum(LeadAssignmentStrategy::class)])
                ->options(LeadAssignmentStrategy::options())
                ->example(LeadAssignmentStrategy::ROUND_ROBIN->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $strategy = $state instanceof LeadAssignmentStrategy ? $state : (is_string($state) ? LeadAssignmentStrategy::tryFrom($state) : null);
                    $record->assignment_strategy = $strategy ?? $record->assignment_strategy;
                }),
            ImportColumn::make('territory')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Northwest')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->territory = $state ? trim($state) : $record->territory;
                }),
            ImportColumn::make('nurture_status')
                ->rules(['nullable', new Enum(LeadNurtureStatus::class)])
                ->options(LeadNurtureStatus::options())
                ->example(LeadNurtureStatus::ACTIVE->value)
                ->fillRecordUsing(function (Lead $record, mixed $state): void {
                    $nurtureStatus = $state instanceof LeadNurtureStatus ? $state : (is_string($state) ? LeadNurtureStatus::tryFrom($state) : null);
                    $record->nurture_status = $nurtureStatus ?? $record->nurture_status;
                }),
            ImportColumn::make('nurture_program')
                ->rules(['nullable', 'string', 'max:255'])
                ->label('Nurture Program')
                ->example('Welcome Drip')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->nurture_program = $state ? trim($state) : $record->nurture_program;
                }),
            ImportColumn::make('web_form_key')
                ->rules(['nullable', 'string', 'max:255'])
                ->label('Web Form Key')
                ->example('homepage-form')
                ->fillRecordUsing(function (Lead $record, ?string $state): void {
                    $record->web_form_key = $state ? trim($state) : $record->web_form_key;
                }),

            ...CustomFields::importer()->forModel(self::getModel())->columns(),
        ];
    }

    public function resolveRecord(): Lead
    {
        $existing = $this->findByEmail();

        return $existing ?? new Lead;
    }

    private function findByEmail(): ?Lead
    {
        $email = $this->extractEmail();

        if ($email === null) {
            return null;
        }

        return Lead::query()
            ->when($this->import->team_id, fn (Builder $query) => $query->where('team_id', $this->import->team_id))
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    private function extractEmail(): ?string
    {
        $email = $this->getOriginalData()['email'] ?? null;

        if (! is_string($email)) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    protected function afterSave(): void
    {
        CustomFields::importer()->forModel($this->record)->saveValues();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your lead import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
