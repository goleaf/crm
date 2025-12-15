<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Developer Login Page
 *
 * Provides a form-based developer login interface for local/testing environments.
 * This page allows developers to quickly switch between user accounts without
 * entering credentials.
 *
 * ## Security
 *
 * - Only available when `APP_ENV` is `local` or `testing`
 * - Route is conditionally registered in routes/web.php
 * - All login attempts are logged with user context
 *
 * ## Multi-Tenancy Support
 *
 * After login, redirects to the user's current team dashboard in the Filament panel.
 * Falls back to root URL if no team is available.
 *
 * @see App\Http\Controllers\Auth\DeveloperLoginController for URL-based login
 * @see routes/web.php for route registration
 * @see docs/auth/developer-login.md for documentation
 */
final class DeveloperLogin extends SimplePage
{
    protected string $view = 'filament.pages.auth.developer-login';

    private static bool $shouldRegisterNavigation = false;

    public ?string $email = null;

    public ?string $name = null;

    public ?string $password = null;

    public function mount(): void
    {
        // Only allow in local and testing environments
        if (! app()->environment(['local', 'testing']) || ! (bool) env('DEV_LOGIN_ENABLED', false)) {
            abort(404);
        }
    }

    public function getTitle(): string
    {
        return __('app.actions.developer_login');
    }

    public function getHeading(): string
    {
        return __('app.actions.developer_login');
    }

    public function getSubheading(): ?string
    {
        return __('app.messages.developer_login_hint');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label(__('app.labels.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label(__('app.labels.name'))
                    ->maxLength(255),
                TextInput::make('password')
                    ->label(__('filament-panels::auth/pages/login.form.password.label'))
                    ->password()
                    ->dehydrated(false),
            ]);
    }

    public function login(): void
    {
        // Only allow in local and testing environments
        if (! app()->environment(['local', 'testing']) || ! (bool) env('DEV_LOGIN_ENABLED', false)) {
            abort(404);
        }

        $data = $this->form->getState();

        /** @var string|null $email */
        $email = $data['email'] ?? null;
        /** @var string|null $name */
        $name = $data['name'] ?? null;

        $user = User::query()->firstOrCreate(
            ['email' => (string) $email],
            [
                'name' => is_string($name) && $name !== '' ? $name : Str::of((string) $email)->before('@')->toString(),
                'email_verified_at' => now(),
                'password' => Str::random(32),
            ],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->ensureUserHasTeam($user);

        Auth::login($user);

        Log::info('Developer login via form', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        $this->redirect($this->resolveRedirectUrl($user));
    }

    private function ensureUserHasTeam(User $user): void
    {
        if ($user->currentTeam !== null) {
            return;
        }

        $firstTeam = $user->allTeams()->first();

        if ($firstTeam !== null) {
            $user->switchTeam($firstTeam);

            return;
        }

        $team = $user->ownedTeams()->create([
            'name' => $user->name . "'s Team",
            'personal_team' => true,
        ]);

        $user->switchTeam($team);
    }

    /**
     * Resolve the redirect URL with tenant support.
     *
     * If the user has a current team, redirects to the tenant dashboard.
     * Falls back to root URL if no team is available.
     */
    private function resolveRedirectUrl(User $user): string
    {
        $team = $user->currentTeam;

        if ($team !== null) {
            try {
                $panel = Filament::getPanel('app');

                return $panel->getUrl($team);
            } catch (\Throwable) {
                // Fall through to default redirect
            }
        }

        return url('/');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('login')
                ->label(__('app.actions.login'))
                ->submit('login'),
        ];
    }
}
