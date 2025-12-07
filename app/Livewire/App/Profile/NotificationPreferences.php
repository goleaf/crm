<?php

declare(strict_types=1);

namespace App\Livewire\App\Profile;

use App\Livewire\BaseLivewireComponent;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;

final class NotificationPreferences extends BaseLivewireComponent
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $preference = $this->authUser()->ensureNotificationPreference();

        $this->form->fill($preference->only([
            'in_app',
            'email',
            'realtime',
            'activity_alerts',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('notifications.preferences.title'))
                    ->aside()
                    ->description(__('notifications.preferences.description'))
                    ->schema([
                        Toggle::make('in_app')
                            ->label(__('notifications.preferences.in_app'))
                            ->helperText(__('notifications.preferences.in_app_help'))
                            ->default(true),
                        Toggle::make('email')
                            ->label(__('notifications.preferences.email'))
                            ->helperText(__('notifications.preferences.email_help'))
                            ->default(true),
                        Toggle::make('realtime')
                            ->label(__('notifications.preferences.realtime'))
                            ->helperText(__('notifications.preferences.realtime_help'))
                            ->default(true),
                        Toggle::make('activity_alerts')
                            ->label(__('notifications.preferences.activity_alerts'))
                            ->helperText(__('notifications.preferences.activity_alerts_help'))
                            ->default(true),
                        Actions::make([
                            Action::make('save')
                                ->label(__('notifications.preferences.save'))
                                ->submit('savePreferences'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function savePreferences(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->sendRateLimitedNotification($exception);

            return;
        }

        $state = $this->form->getState();

        $this->authUser()
            ->ensureNotificationPreference()
            ->fill($state)
            ->save();

        $this->sendNotification(__('notifications.preferences.saved'));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.app.profile.notification-preferences');
    }
}
