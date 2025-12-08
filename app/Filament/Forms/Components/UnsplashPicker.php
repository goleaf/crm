<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Models\UnsplashAsset;
use App\Services\Media\UnsplashService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;

final class UnsplashPicker extends Field
{
    protected string $view = 'filament.forms.components.unsplash-picker';

    private ?string $collection = null;

    private bool $multiple = false;

    protected int $columns = 3;

    private ?string $orientation = null;

    private ?string $defaultQuery = null;

    public function collection(?string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function defaultQuery(?string $query): static
    {
        $this->defaultQuery = $query;

        return $this;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function getDefaultQuery(): ?string
    {
        return $this->defaultQuery;
    }

    public function suffixActions(array $actions): static
    {
        return $this->suffixActions([
            Action::make('selectFromUnsplash')
                ->label(__('app.actions.select_from_unsplash'))
                ->icon('heroicon-o-photo')
                ->modalHeading(__('app.modals.select_unsplash_photo'))
                ->modalWidth(config('unsplash.filament.modal_width', 'xl'))
                ->modalContent(fn (): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('filament.modals.unsplash-picker', [
                    'field' => $this,
                ]))
                ->action(function (array $data): void {
                    $unsplashService = resolve(UnsplashService::class);
                    $photoData = $unsplashService->getPhoto($data['photo_id']);

                    if ($photoData) {
                        $asset = UnsplashAsset::findOrCreateFromApi($photoData);

                        if (config('unsplash.defaults.auto_download')) {
                            $this->downloadPhoto($asset, $unsplashService);
                        }

                        $this->state($this->multiple ? [...$this->getState(), $asset->id] : $asset->id);
                    }
                }),
        ]);
    }

    private function downloadPhoto(UnsplashAsset $asset, UnsplashService $unsplashService): void
    {
        if ($asset->isDownloaded()) {
            return;
        }

        $url = $asset->getUrl('regular');
        $filename = "{$asset->unsplash_id}.jpg";

        $localPath = $unsplashService->downloadPhoto($url, $filename);

        if ($localPath) {
            $asset->update([
                'local_path' => $localPath,
                'downloaded_at' => now(),
            ]);

            // Track download with Unsplash API
            if ($asset->download_location) {
                $unsplashService->trackDownload($asset->download_location);
            }
        }
    }
}
