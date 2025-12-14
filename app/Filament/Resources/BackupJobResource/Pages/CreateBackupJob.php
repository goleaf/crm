<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackupJobResource\Pages;

use App\Filament\Resources\BackupJobResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBackupJob extends CreateRecord
{
    protected static string $resource = BackupJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()->currentTeam->id;
        $data['created_by'] = auth()->id();

        // Set default configuration if not provided
        if (empty($data['backup_config'])) {
            $data['backup_config'] = [
                'async' => true,
                'retention_days' => 30,
                'files' => ['storage/app', '.env'],
            ];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Automatically start the backup after creation
        $backupService = resolve(\App\Services\DataQuality\BackupService::class);
        $backupService->processBackup($this->record);
    }
}
