<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\People;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Relaticle\CustomFields\Facades\CustomFields;

final class PeopleExporter extends BaseExporter
{
    protected static ?string $model = People::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('app.labels.id')),
            ExportColumn::make('team.name'),
            ExportColumn::make('creator.name'),
            ExportColumn::make('creation_source')
                ->label(__('app.labels.creation_source'))
                ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state),
            ExportColumn::make('company.name'),
            ExportColumn::make('name'),
            ExportColumn::make('job_title')
                ->label('Job Title'),
            ExportColumn::make('department'),
            ExportColumn::make('primary_email')
                ->label('Primary Email'),
            ExportColumn::make('alternate_email')
                ->label('Alternate Email'),
            ExportColumn::make('phone_mobile')
                ->label('Mobile Phone'),
            ExportColumn::make('phone_office')
                ->label('Office Phone'),
            ExportColumn::make('phone_home')
                ->label('Home Phone'),
            ExportColumn::make('phone_fax')
                ->label('Fax'),
            ExportColumn::make('lead_source')
                ->label('Lead Source'),
            ExportColumn::make('reportsTo.name')
                ->label('Reports To'),
            ExportColumn::make('birthdate')
                ->label('Birthday'),
            ExportColumn::make('assistant_name')
                ->label('Assistant Name'),
            ExportColumn::make('assistant_email')
                ->label('Assistant Email'),
            ExportColumn::make('assistant_phone')
                ->label('Assistant Phone'),
            ExportColumn::make('address_street')
                ->label('Street'),
            ExportColumn::make('address_city')
                ->label('City'),
            ExportColumn::make('address_state')
                ->label('State/Province'),
            ExportColumn::make('address_postal_code')
                ->label('Postal Code'),
            ExportColumn::make('address_country')
                ->label('Country'),
            ExportColumn::make('segments')
                ->label('Segments')
                ->formatStateUsing(function (mixed $state): ?string {
                    if (in_array($state, [null, '', []], true)) {
                        return null;
                    }

                    // Handle JSON string
                    if (is_string($state)) {
                        $decoded = json_decode($state, true);
                        $state = is_array($decoded) ? $decoded : [$state];
                    }

                    // Handle array
                    if (is_array($state)) {
                        return implode(', ', $state);
                    }

                    return (string) $state;
                }),
            ExportColumn::make('social_links')
                ->label('Social Links')
                ->formatStateUsing(function (mixed $state): ?string {
                    if (in_array($state, [null, '', []], true)) {
                        return null;
                    }

                    // Handle JSON string
                    if (is_string($state)) {
                        return $state;
                    }

                    // Handle array
                    if (is_array($state)) {
                        return json_encode($state);
                    }

                    return (string) $state;
                }),
            ExportColumn::make('is_portal_user')
                ->label('Portal User')
                ->formatStateUsing(fn (mixed $state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('portal_username')
                ->label('Portal Username'),
            ExportColumn::make('sync_enabled')
                ->label('Sync Enabled')
                ->formatStateUsing(fn (mixed $state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('sync_reference')
                ->label('Sync Reference'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('deleted_at'),

            ...CustomFields::exporter()->forModel(self::getModel())->columns(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your people export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
