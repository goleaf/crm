<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\Pages;

use App\Filament\Resources\SecurityGroupResource;
use App\Services\SecurityGroup\SecurityGroupService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateSecurityGroup extends CreateRecord
{
    protected static string $resource = SecurityGroupResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = resolve(SecurityGroupService::class);

        // Validate hierarchy if parent is set
        if (! empty($data['parent_id'])) {
            $parent = \App\Models\SecurityGroup::find($data['parent_id']);
            if (! $parent) {
                Notification::make()
                    ->title(__('app.notifications.invalid_parent_group'))
                    ->danger()
                    ->send();

                throw new \Exception('Invalid parent group');
            }
        }

        return $service->createSecurityGroup($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
