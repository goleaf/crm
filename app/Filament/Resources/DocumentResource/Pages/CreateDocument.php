<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

final class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove upload, handled after save
        unset($data['upload']);
        unset($data['share_user_ids'], $data['shares']);

        return $data;
    }

    private function afterCreate(): void
    {
        $upload = $this->data['upload'] ?? null;

        if ($upload !== null && $this->record instanceof Document) {
            $path = is_array($upload) ? ($upload['path'] ?? $upload[0] ?? null) : (string) $upload;

            if ($path) {
                $this->createVersionFromUpload($this->record, $path);
            }
        }

        if ($this->record instanceof Document) {
            $this->syncQuickShares($this->record, (array) ($this->data['share_user_ids'] ?? []));
        }
    }

    private function createVersionFromUpload(Document $document, string $path): void
    {
        $version = new DocumentVersion([
            'document_id' => $document->getKey(),
            'team_id' => $document->team_id,
            'uploaded_by' => Auth::id(),
            'file_path' => $path,
            'disk' => 'public',
            'notes' => 'Initial upload',
        ]);

        $version->save();
    }

    /**
     * @param  array<int|string>  $userIds
     */
    private function syncQuickShares(Document $document, array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        collect($userIds)
            ->filter()
            ->unique()
            ->each(function ($userId) use ($document): void {
                $document->shares()->firstOrCreate(
                    [
                        'document_id' => $document->getKey(),
                        'user_id' => (int) $userId,
                    ],
                    [
                        'team_id' => $document->team_id,
                        'permission' => 'view',
                    ]
                );
            });
    }
}
