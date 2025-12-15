<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShareLinkResource\Pages;

use App\Filament\Resources\ShareLinkResource;
use App\Services\ShareLink\ShareLinkService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListShareLinks extends ListRecords
{
    protected static string $resource = ShareLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_stats')
                ->label(__('app.actions.view_statistics'))
                ->icon('heroicon-o-chart-bar')
                ->modalHeading(__('app.modals.sharelink_statistics'))
                ->modalContent(function (ShareLinkService $service): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View {
                    $stats = $service->getGlobalStats();

                    return view('filament.modals.sharelink-stats', ['stats' => $stats]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('app.actions.close'))
                ->color('gray'),
        ];
    }
}
