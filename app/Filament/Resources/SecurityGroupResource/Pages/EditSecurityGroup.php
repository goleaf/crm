<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\Pages;

use App\Filament\Resources\SecurityGroupResource;
use App\Services\SecurityGroup\SecurityGroupService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditSecurityGroup extends EditRecord
{
    protected static string $resource = SecurityGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (): void {
                    $service = resolve(SecurityGroupService::class);

                    if (! $service->validateHierarchy($this->getRecord(), null)) {
                        Notification::make()
                            ->title(__('app.notifications.cannot_delete_group'))
                            ->body(__('app.notifications.group_has_dependencies'))
                            ->danger()
                            ->send();

                        throw new \Exception('Cannot delete group with dependencies');
                    }
                }),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = resolve(SecurityGroupService::class);

        // Validate hierarchy if parent is being changed
        if (isset($data['parent_id']) && $data['parent_id'] !== $record->parent_id && ! $service->validateHierarchy($record, $data['parent_id'])) {
            Notification::make()
                ->title(__('app.notifications.invalid_hierarchy'))
                ->body(__('app.notifications.circular_reference_detected'))
                ->danger()
                ->send();

            throw new \Exception('Invalid hierarchy - circular reference detected');
        }

        return $service->updateSecurityGroup($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
