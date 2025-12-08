<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Filament\Resources\PeopleResource;
use App\Models\People;
use App\Services\Contacts\ContactDuplicateDetectionService;
use App\Services\Contacts\ContactMergeService;
use App\Services\Contacts\VCardService;
use App\Services\Example\ExampleQueryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Example Filament page demonstrating service container integration patterns.
 *
 * Services are injected via method parameters in action callbacks.
 * This keeps actions focused on UI concerns while delegating business logic to services.
 */
final class ExampleServiceIntegration extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

    /**
     * Header actions demonstrating service injection patterns.
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->findDuplicatesAction(),
            $this->mergeContactAction(),
            $this->exportVCardAction(),
            $this->viewMetricsAction(),
        ];
    }

    /**
     * Find duplicates action - injects DuplicateDetectionService.
     */
    private function findDuplicatesAction(): Action
    {
        return Action::make('findDuplicates')
            ->label(__('app.actions.find_duplicates'))
            ->icon('heroicon-o-document-duplicate')
            ->color('warning')
            ->action(function (ContactDuplicateDetectionService $service): void {
                $duplicates = $service->findDuplicates($this->record);

                if ($duplicates->isEmpty()) {
                    Notification::make()
                        ->title(__('app.notifications.no_duplicates_found'))
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('app.notifications.duplicates_found', ['count' => $duplicates->count()]))
                    ->warning()
                    ->body(__('app.notifications.duplicates_found_body'))
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(route('filament.app.resources.people.duplicates', $this->record)),
                    ])
                    ->send();
            });
    }

    /**
     * Merge contact action - injects ContactMergeService.
     */
    private function mergeContactAction(): Action
    {
        return Action::make('merge')
            ->label(__('app.actions.merge_contact'))
            ->icon('heroicon-o-arrows-pointing-in')
            ->color('danger')
            ->form([
                Select::make('duplicate_id')
                    ->label(__('app.labels.duplicate_contact'))
                    ->options(fn (): array => People::where('team_id', $this->record->team_id)
                        ->whereKeyNot($this->record->getKey())
                        ->pluck('name', 'id')
                        ->toArray())
                    ->required()
                    ->searchable()
                    ->helperText(__('app.helpers.select_duplicate_to_merge')),
            ])
            ->action(function (array $data, ContactMergeService $service): void {
                $duplicate = People::findOrFail($data['duplicate_id']);

                try {
                    $service->merge(
                        primary: $this->record,
                        duplicate: $duplicate,
                        userId: auth()->id(),
                        fieldSelections: []
                    );

                    Notification::make()
                        ->title(__('app.notifications.contacts_merged'))
                        ->success()
                        ->send();

                    $this->redirect(route('filament.app.resources.people.view', $this->record));
                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('app.notifications.merge_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading(__('app.modals.merge_contacts'))
            ->modalDescription(__('app.modals.merge_contacts_description'))
            ->modalSubmitActionLabel(__('app.actions.merge'));
    }

    /**
     * Export vCard action - injects VCardService.
     */
    private function exportVCardAction(): Action
    {
        return Action::make('exportVCard')
            ->label(__('app.actions.export_vcard'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (VCardService $service) {
                $vcard = $service->export($this->record);

                return response()->streamDownload(function () use ($vcard): void {
                    echo $vcard;
                }, "{$this->record->name}.vcf", [
                    'Content-Type' => 'text/vcard',
                ]);
            });
    }

    /**
     * View metrics action - injects ExampleQueryService.
     */
    private function viewMetricsAction(): Action
    {
        return Action::make('viewMetrics')
            ->label(__('app.actions.view_metrics'))
            ->icon('heroicon-o-chart-bar')
            ->color('info')
            ->modalContent(function (ExampleQueryService $service): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View {
                $metrics = $service->getContactMetrics($this->record);

                return view('filament.modals.contact-metrics', [
                    'contact' => $this->record,
                    'metrics' => $metrics,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('app.actions.close'));
    }
}
