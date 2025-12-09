<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\People;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Relaticle\CustomFields\Facades\CustomFields;

final class PeopleImporter extends BaseImporter
{
    protected static ?string $model = People::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->guess(['name', 'full_name', 'person_name'])
                ->rules(['required', 'string', 'max:255'])
                ->example('John Doe')
                ->fillRecordUsing(function (People $record, string $state, Importer $importer): void {
                    $record->name = $state;

                    // Set team and creator for new records
                    if (! $record->exists) {
                        $record->team_id = $importer->import->team_id;
                        $record->creator_id = $importer->import->user_id;
                        $record->creation_source = CreationSource::IMPORT;
                    }
                }),

            ImportColumn::make('company_name')
                ->requiredMapping()
                ->label('Company Name')
                ->guess(['company_name', 'Company'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Acme Corporation')
                ->fillRecordUsing(function (People $record, string $state, Importer $importer): void {
                    // Since company_name is required, we should always have a value
                    if (! $importer->import->team_id) {
                        throw new \RuntimeException('Team ID is required for import');
                    }

                    try {
                        $company = Company::firstOrCreate(
                            [
                                'name' => trim($state),
                                'team_id' => $importer->import->team_id,
                            ],
                            [
                                'creator_id' => $importer->import->user_id,
                                'creation_source' => CreationSource::IMPORT,
                            ],
                        );

                        $record->company_id = $company->getKey();
                    } catch (\Exception $e) {
                        report($e);

                        throw $e; // Re-throw to fail the import for this row
                    }
                }),

            ImportColumn::make('primary_email')
                ->label('Primary Email')
                ->guess(['email', 'primary_email'])
                ->rules(['nullable', 'email', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->primary_email = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('alternate_email')
                ->label('Alternate Email')
                ->guess(['alternate_email', 'secondary_email'])
                ->rules(['nullable', 'email', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->alternate_email = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('phone_mobile')
                ->label('Mobile Phone')
                ->guess(['mobile', 'mobile_phone', 'cell'])
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->phone_mobile = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('phone_office')
                ->label('Office Phone')
                ->guess(['office_phone', 'work_phone'])
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->phone_office = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('phone_home')
                ->label('Home Phone')
                ->guess(['home_phone'])
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->phone_home = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('phone_fax')
                ->label('Fax')
                ->guess(['fax'])
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->phone_fax = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('job_title')
                ->label('Job Title')
                ->guess(['job_title', 'title'])
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->job_title = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('department')
                ->label('Department')
                ->guess(['department'])
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->department = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('lead_source')
                ->label('Lead Source')
                ->guess(['lead_source', 'source'])
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->lead_source = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('reports_to')
                ->label('Reports To')
                ->guess(['reports_to', 'manager'])
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state, Importer $importer): void {
                    if (! $importer->import->team_id || blank($state)) {
                        $record->reports_to_id = null;

                        return;
                    }

                    $manager = People::query()
                        ->where('team_id', $importer->import->team_id)
                        ->where('name', trim($state))
                        ->first();

                    $record->reports_to_id = $manager?->getKey();
                }),

            ImportColumn::make('birthdate')
                ->label('Birthday')
                ->guess(['birthday', 'birthdate', 'dob'])
                ->rules(['nullable', 'date'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->birthdate = blank($state) ? null : \Illuminate\Support\Facades\Date::parse($state)->toDateString();
                }),

            ImportColumn::make('assistant_name')
                ->label('Assistant Name')
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->assistant_name = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('assistant_email')
                ->label('Assistant Email')
                ->rules(['nullable', 'email', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->assistant_email = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('assistant_phone')
                ->label('Assistant Phone')
                ->rules(['nullable', 'string', 'max:50'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    $record->assistant_phone = filled($state) ? trim($state) : null;
                }),

            ImportColumn::make('segments')
                ->label('Segments')
                ->rules(['nullable', 'string'])
                ->example('Prospect, Customer')
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $record->segments = collect(explode(',', $state))
                        ->map(fn (string $segment): string => trim($segment))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                }),

            ImportColumn::make('linkedin')
                ->label('LinkedIn URL')
                ->rules(['nullable', 'url', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $links = $record->social_links ?? [];
                    $links['linkedin'] = trim($state);
                    $record->social_links = $links;
                }),

            ImportColumn::make('twitter')
                ->label('Twitter URL')
                ->rules(['nullable', 'url', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $links = $record->social_links ?? [];
                    $links['twitter'] = trim($state);
                    $record->social_links = $links;
                }),

            ImportColumn::make('facebook')
                ->label('Facebook URL')
                ->rules(['nullable', 'url', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $links = $record->social_links ?? [];
                    $links['facebook'] = trim($state);
                    $record->social_links = $links;
                }),

            ImportColumn::make('github')
                ->label('GitHub URL')
                ->rules(['nullable', 'url', 'max:255'])
                ->fillRecordUsing(function (People $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $links = $record->social_links ?? [];
                    $links['github'] = trim($state);
                    $record->social_links = $links;
                }),

            ...CustomFields::importer()->forModel(self::getModel())->columns(),
        ];
    }

    public function resolveRecord(): People
    {
        $person = $this->findByEmail();

        return $person ?? new People;
    }

    private function findByEmail(): ?People
    {
        $emails = $this->extractEmails();

        if ($emails === []) {
            return null;
        }

        return People::query()
            ->when($this->import->team_id, fn (Builder $query) => $query->where('team_id', $this->import->team_id))
            ->where(function (Builder $query) use ($emails): void {
                $query->whereIn('primary_email', $emails)
                    ->orWhereIn('alternate_email', $emails)
                    ->orWhereHas('customFieldValues', function (Builder $query) use ($emails): void {
                        $query->whereRelation('customField', 'code', 'emails')
                            ->where(function (Builder $query) use ($emails): void {
                                foreach ($emails as $email) {
                                    $query->orWhereJsonContains('json_value', $email);
                                }
                            });
                    });
            })
            ->first();
    }

    /**
     * Extract and validate emails from original data
     *
     * @return array<int, string>
     */
    private function extractEmails(): array
    {
        $emails = collect([
            $this->getOriginalData()['primary_email'] ?? null,
            $this->getOriginalData()['alternate_email'] ?? null,
        ]);

        $emailsField = $this->getOriginalData()['custom_fields_emails'] ?? null;

        if (! empty($emailsField)) {
            $emails = $emails->merge(
                is_string($emailsField)
                    ? explode(',', $emailsField)
                    : (array) $emailsField,
            );
        }

        return $emails
            ->map(fn (mixed $email): string => trim((string) $email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();
    }

    protected function afterSave(): void
    {
        CustomFields::importer()->forModel($this->record)->saveValues();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your people import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
