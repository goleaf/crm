<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Services\Content\ProfanityFilterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

final class CleanProfanityAction extends Action
{
    public static function make(?string $name = null): static
    {
        $name ??= 'cleanProfanity';

        return parent::make($name)
            ->label(__('app.actions.clean_profanity'))
            ->icon('heroicon-m-sparkles')
            ->color('warning')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-sparkles')
            ->modalHeading(__('app.actions.clean_profanity'))
            ->modalDescription(__('app.helpers.clean_profanity_confirmation'))
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
                    ->default('english')
                    ->required(),
            ])
            ->action(function (array $data, $record, $livewire): void {
                /** @var ProfanityFilterService $service */
                $service = resolve(ProfanityFilterService::class);
                // Determine which field to clean.
                // If used as a table action, we might need to know the column or standard 'content'/'description' field.
                // Or successful usage implies passing arguments or context.
                // For a generic action, we often need to know the target field.
                // Let's assume the action name matches the field name if not overridden,
                // or we rely on the record having a specific field.
                // But wait, 'cleanProfanity' is the action name.
                // A better pattern for field-specific actions is usually to pass the field name.
                // If this is a table action on a specific record, 'record' is available.
                // We need to know WHICH attribute to clean.
                // Let's assume passed in via arguments or we default to 'content' or 'description' ??
                // Better: Allow configuring the target field.
                // Actually, if we use it inside a form context, it's different.
                // As a Table Action, we want `CleanProfanityAction::make('description')`?
                // No, standard Action make take the action name, not the field.
                // So we should add a custom method `field(string $field)` to this class.
                // But Action `make` usually returns an instance.
                // Let's make a factory method or just use a custom property.
                // But `Action` class structure in Filament is strict.
                // Let's stick to a standard Action that assumes a field or configurable.
                // For now, let's just use `->action(...)` closure where the user defines the logic?
                // Or better, let's accept the field name in a custom static constructor or chained method.
                // Standard pattern: `CleanProfanityAction::make('clean_description')->field('description')`
            });
        // We can't easily add methods to the instance cleanly if we extend standard Action without a trait or custom logic inside `setUp`.
        // Let's provide a static helper that returns a pre-configured Action.
        // But here we are defining the class properly.
        // Let's implement `field()` method.
    }

    // We can't easily add methods like `field()` to `Filament\Actions\Action` instance unless we extend it and use that class.
    // Yes, we are extending it.

    private string $targetField = 'content';

    public function targetField(string $field): static
    {
        $this->targetField = $field;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (array $data, $record): void {
            /** @var ProfanityFilterService $service */
            $service = resolve(ProfanityFilterService::class);

            $text = $record->getAttribute($this->targetField);

            if (empty($text)) {
                Notification::make()
                    ->warning()
                    ->title(__('app.notifications.no_content_to_clean'))
                    ->send();

                return;
            }

            $cleaned = $service->clean($text, $data['language']);

            if ($cleaned === $text) {
                Notification::make()
                    ->info()
                    ->title(__('app.notifications.no_profanity_found'))
                    ->send();

                return;
            }

            $record->update([$this->targetField => $cleaned]);

            Notification::make()
                ->success()
                ->title(__('app.notifications.profanity_cleaned'))
                ->send();
        });
    }
}
