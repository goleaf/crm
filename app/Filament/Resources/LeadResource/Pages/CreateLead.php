<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadResource\Forms\CreateLeadForm;
use App\Models\Lead;
use App\Services\LeadDuplicateDetectionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

final class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected static ?string $title = 'Create Lead';

    public function form(Schema $schema): Schema
    {
        return CreateLeadForm::get($schema);
    }

    private function afterCreate(): void
    {
        $this->checkForDuplicates();
    }

    private function checkForDuplicates(): void
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();

        $service = app(LeadDuplicateDetectionService::class);
        $duplicates = $service->find($lead, threshold: 60.0, limit: 5);

        if ($duplicates->isEmpty()) {
            return;
        }

        $message = $this->formatDuplicatesMessage($duplicates);

        Notification::make()
            ->title('Potential duplicates detected')
            ->body($message)
            ->warning()
            ->persistent()
            ->send();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{lead: Lead, score: float}>  $duplicates
     */
    private function formatDuplicatesMessage(\Illuminate\Support\Collection $duplicates): string
    {
        $lines = ['The following similar leads were found:'];

        foreach ($duplicates as $duplicate) {
            /** @var Lead $lead */
            $lead = $duplicate['lead'];
            $score = $duplicate['score'];

            $lines[] = sprintf(
                '• %s (%s) - %d%% match',
                $lead->name,
                $lead->email ?? 'no email',
                (int) $score
            );
        }

        return implode("\n", $lines);
    }

    private function getFormColumns(): int
    {
        return 1;
    }

    private function getFormMaxWidth(): string
    {
        return 'full';
    }
}
