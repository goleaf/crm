<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\URL;

final class Login extends \Filament\Auth\Pages\Login
{
    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (
            ! app()->environment(['local', 'testing'])
            || ! (bool) env('DEV_LOGIN_ENABLED', false)
            || ! \Illuminate\Support\Facades\Route::has('dev.login')
        ) {
            return parent::getSubheading();
        }

        // Get the first user for quick dev login
        $user = User::first();

        if (! $user) {
            return new \Illuminate\Support\HtmlString(
                '<span class="text-gray-500 dark:text-gray-400">'
                . __('app.messages.developer_login_hint')
                . ' - ' . __('app.messages.developer_login_user_not_found', ['email' => 'any'])
                . '</span>',
            );
        }

        $devLoginUrl = URL::temporarySignedRoute('dev.login', now()->addMinutes(30), [
            'email' => $user->email,
            'redirect' => filament()->getHomeUrl(),
        ]);

        return new \Illuminate\Support\HtmlString(
            '<a href="' . $devLoginUrl . '" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 font-medium">'
            . __('app.actions.developer_login') . ' (' . $user->name . ')'
            . '</a>',
        );
    }
}
