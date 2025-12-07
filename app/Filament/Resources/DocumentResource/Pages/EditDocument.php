<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['upload'], $data['share_user_ids'], $data['shares']);

        return $data;
    }

    private function afterSave(): void
    {
        if ($this->record instanceof Document) {
            $this->syncQuickShares((array) ($this->data['share_user_ids'] ?? []));
        }
    }

    /**
     * @param  array<int|string>  $userIds
     */
    private function syncQuickShares(array $userIds): void
    {
        if (! $this->record instanceof Document || $userIds === []) {
            return;
        }

        collect($userIds)
            ->filter()
            ->unique()
            ->each(function ($userId): void {
                $this->record?->shares()->firstOrCreate(
                    [
                        'document_id' => $this->record->getKey(),
                        'user_id' => (int) $userId,
                    ],
                    [
                        'team_id' => $this->record->team_id,
                        'permission' => 'view',
                    ]
                );
            });
    }
}
