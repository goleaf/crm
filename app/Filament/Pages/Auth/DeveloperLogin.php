<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public ?string $selectedUser = null;

    public function mount(): void
    {
        // Only allow in local and testing environments
        if (! app()->environment(['local', 'testing'])) {
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
                Select::make('selectedUser')
                    ->label(__('app.labels.user'))
                    ->options(fn (): array => User::query()
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->placeholder(__('app.placeholders.select_user')),
            ]);
    }

    public function login(): void
    {
        // Only allow in local and testing environments
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $data = $this->form->getState();

        $user = User::find($data['selectedUser']);

        if (! $user) {
            $this->addError('selectedUser', __('app.messages.developer_login_user_not_found', ['email' => 'selected user']));

            return;
        }

        Auth::login($user);

        Log::info('Developer login via form', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        $this->redirect($this->resolveRedirectUrl($user));
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
