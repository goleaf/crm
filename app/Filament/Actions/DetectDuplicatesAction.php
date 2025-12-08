<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Account;
use App\Models\Lead;
use App\Services\AccountDuplicateDetectionService;
use App\Services\LeadDuplicateDetectionService;
use App\Support\Helpers\ArrayHelper;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

final class DetectDuplicatesAction
{
    public static function make(Lead|Account $record): Action
    {
        return Action::make('detectDuplicates')
            ->label('Check for Duplicates')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->color('warning')
            ->action(function () use ($record): void {
                $duplicates = self::findDuplicates($record);

                if ($duplicates->isEmpty()) {
                    Notification::make()
                        ->title('No duplicates found')
                        ->success()
                        ->send();

                    return;
                }

                $message = self::formatDuplicatesMessage($duplicates, $record);

                Notification::make()
                    ->title('Potential duplicates found')
                    ->body($message)
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }

    /**
     * @return Collection<int, array{lead: Lead, score: float}>|Collection<int, array{account: Account, score: float}>
     */
    private static function findDuplicates(Lead|Account $record): Collection
    {
        if ($record instanceof Lead) {
            $service = resolve(LeadDuplicateDetectionService::class);

            return $service->find($record, threshold: 60.0, limit: 5);
        }

        $service = resolve(AccountDuplicateDetectionService::class);

        return $service->find($record, threshold: 60.0, limit: 5);
    }

    private static function formatDuplicatesMessage(Collection $duplicates, Lead|Account $record): string
    {
        $lines = [];

        foreach ($duplicates as $duplicate) {
            $score = $duplicate['score'];

            if ($record instanceof Lead) {
                /** @var Lead $lead */
                $lead = $duplicate['lead'];
                $lines[] = sprintf(
                    '• %s (%s) - %d%% match',
                    $lead->name,
                    $lead->email ?? 'no email',
                    (int) $score
                );
            } else {
                /** @var Account $account */
                $account = $duplicate['account'];
                $lines[] = sprintf(
                    '• %s (%s) - %d%% match',
                    $account->name,
                    $account->website ?? 'no website',
                    (int) $score
                );
            }
        }

        return ArrayHelper::joinList($lines, PHP_EOL, emptyPlaceholder: '');
    }
}
