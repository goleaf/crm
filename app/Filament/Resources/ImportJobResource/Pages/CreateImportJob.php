<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportJobResource\Pages;

use App\Filament\Resources\ImportJobResource;
use App\Services\Import\ImportService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;

final class CreateImportJob extends CreateRecord
{
    protected static string $resource = ImportJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['team_id'] = auth()->user()->currentTeam?->id;

        // Handle file upload and create import job
        if (isset($data['file_path']) && $data['file_path'] instanceof UploadedFile) {
            $file = $data['file_path'];

            $importService = resolve(ImportService::class);
            $importJob = $importService->createImportJob(
                $file,
                $data['model_type'],
                $data['name'],
                $data['team_id'],
                $data['user_id'],
            );

            // Return the created import job data
            return $importJob->toArray();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
