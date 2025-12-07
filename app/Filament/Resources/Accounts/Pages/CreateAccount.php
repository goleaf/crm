<?php

declare(strict_types=1);

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Services\AccountDuplicateDetectionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    private function afterCreate(): void
    {
        $this->checkForDuplicates();
    }

    private function checkForDuplicates(): void
    {
        /** @var Account $account */
        $account = $this->getRecord();

        $service = app(AccountDuplicateDetectionService::class);
        $duplicates = $service->find($account, threshold: 60.0, limit: 5);

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
     * @param  \Illuminate\Support\Collection<int, array{account: Account, score: float}>  $duplicates
     */
    private function formatDuplicatesMessage(\Illuminate\Support\Collection $duplicates): string
    {
        $lines = ['The following similar accounts were found:'];

        foreach ($duplicates as $duplicate) {
            /** @var Account $account */
            $account = $duplicate['account'];
            $score = $duplicate['score'];

            $lines[] = sprintf(
                '• %s (%s) - %d%% match',
                $account->name,
                $account->website ?? 'no website',
                (int) $score
            );
        }

        return implode("\n", $lines);
    }
}
