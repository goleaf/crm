<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Services\Content\ProfanityFilterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Reusable Filament action for cleaning profanity from text fields.
 *
 * Usage in resources:
 * CleanProfanityAction::make('description')
 */
final class CleanProfanityAction
{
    public static function make(string $fieldName = 'content'): Action
    {
        return Action::make('cleanProfanity')
            ->label(__('app.actions.clean_profanity'))
            ->icon('heroicon-o-shield-check')
            ->color('warning')
            ->form([
                Textarea::make('text')
                    ->label(__('app.labels.text_to_clean'))
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),

                Select::make('language')
                    ->label(__('app.labels.language'))
                    ->options([
                        'english' => __('app.languages.english'),
                        'spanish' => __('app.languages.spanish'),
                        'german' => __('app.languages.german'),
                        'french' => __('app.languages.french'),
                        'all' => __('app.languages.all'),
                    ])
                    ->default(config('blasp.default_language', 'english'))
                    ->required(),

                TextInput::make('mask_character')
                    ->label(__('app.labels.mask_character'))
                    ->default('*')
                    ->maxLength(1)
                    ->placeholder('*'),
            ])
            ->action(function (array $data, $record, $set) use ($fieldName): void {
                $service = resolve(ProfanityFilterService::class);

                $result = $data['language'] === 'all'
                    ? $service->checkAllLanguages($data['text'], $data['mask_character'])
                    : $service->analyze($data['text'], $data['language'], $data['mask_character']);

                if ($result['has_profanity']) {
                    // Update the field with cleaned text
                    if ($record) {
                        $record->update([$fieldName => $result['clean_text']]);
                    } else {
                        $set($fieldName, $result['clean_text']);
                    }

                    Notification::make()
                        ->title(__('app.notifications.profanity_cleaned'))
                        ->body(__('app.notifications.profanity_cleaned_body', [
                            'count' => $result['count'],
                        ]))
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title(__('app.notifications.no_profanity_found'))
                        ->success()
                        ->send();
                }
            })
            ->modalHeading(__('app.modals.clean_profanity'))
            ->modalDescription(__('app.modals.clean_profanity_description'))
            ->modalSubmitActionLabel(__('app.actions.clean'))
            ->modalWidth('lg');
    }

    /**
     * Create action for table bulk operations.
     */
    public static function makeBulk(string $fieldName = 'content'): Action
    {
        return Action::make('cleanProfanityBulk')
            ->label(__('app.actions.clean_profanity'))
            ->icon('heroicon-o-shield-check')
            ->color('warning')
            ->form([
                Select::make('language')
                    ->label(__('app.labels.language'))
                    ->options([
                        'english' => __('app.languages.english'),
                        'spanish' => __('app.languages.spanish'),
                        'german' => __('app.languages.german'),
                        'french' => __('app.languages.french'),
                        'all' => __('app.languages.all'),
                    ])
                    ->default(config('blasp.default_language', 'english'))
                    ->required(),

                TextInput::make('mask_character')
                    ->label(__('app.labels.mask_character'))
                    ->default('*')
                    ->maxLength(1),
            ])
            ->action(function (array $data, $records) use ($fieldName): void {
                $service = resolve(ProfanityFilterService::class);
                $cleaned = 0;

                foreach ($records as $record) {
                    $text = $record->{$fieldName};
                    if (! $text) {
                        continue;
                    }

                    $result = $data['language'] === 'all'
                        ? $service->checkAllLanguages($text, $data['mask_character'])
                        : $service->analyze($text, $data['language'], $data['mask_character']);

                    if ($result['has_profanity']) {
                        $record->update([$fieldName => $result['clean_text']]);
                        $cleaned++;
                    }
                }

                Notification::make()
                    ->title(__('app.notifications.bulk_profanity_cleaned'))
                    ->body(__('app.notifications.bulk_profanity_cleaned_body', [
                        'count' => $cleaned,
                        'total' => $records->count(),
                    ]))
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading(__('app.modals.clean_profanity_bulk'))
            ->modalDescription(__('app.modals.clean_profanity_bulk_description'))
            ->deselectRecordsAfterCompletion();
    }
}
