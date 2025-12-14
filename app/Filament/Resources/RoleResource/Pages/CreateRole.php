<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Services\Role\RoleManagementService;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = resolve(RoleManagementService::class);

        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        // Set team_id for multi-tenant setup
        $data['team_id'] = filament()->getTenant()?->id;

        return $service->createRole($data, $permissions);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
