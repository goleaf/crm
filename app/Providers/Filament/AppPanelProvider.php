<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\CreateTeam;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditTeam;
use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\LeadTrendChart;
use App\Filament\Widgets\PipelinePerformanceChart;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentActivity;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\SetLocale;
use App\Listeners\SwitchTeam;
use App\Models\Team;
use App\Rules\CleanContent;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Events\TenantSet;
use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Jetstream\Features;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Relaticle\CustomFields\CustomFieldsPlugin;
use Stephenjude\FilamentFeatureFlag\FeatureFlagPlugin;

final class AppPanelProvider extends PanelProvider
{
    /**
     * Perform post-registration booting of components.
     */
    public function boot(): void
    {
        /**
         * Listen and switch team if tenant was changed
         */
        Event::listen(
            TenantSet::class,
            SwitchTeam::class,
        );

        Action::configureUsing(fn (Action $action): Action => $action->size(Size::Small)->iconPosition('before'));
        TextInput::configureUsing(fn (TextInput $input): TextInput => $input->rule(new CleanContent));
        Textarea::configureUsing(fn (Textarea $textarea): Textarea => $textarea->rule(new CleanContent));
        MarkdownEditor::configureUsing(fn (MarkdownEditor $editor): MarkdownEditor => $editor->rule(new CleanContent));
        RichEditor::configureUsing(fn (RichEditor $editor): RichEditor => $editor->rule(new CleanContent));
        Section::configureUsing(fn (Section $section): Section => $section->compact());
        Table::configureUsing(function (Table $table): Table {
            $pluralLabel = $table->getPluralModelLabel() ?? __('app.labels.records');
            $singularLabel = $table->getModelLabel() ?? __('app.labels.record');

            $createAction = null;

            foreach ($table->getHeaderActions() as $action) {
                if ($action instanceof ActionGroup) {
                    foreach ($action->getFlatActions() as $groupedAction) {
                        if ($groupedAction instanceof Action && ($groupedAction->getName() === 'create' || $groupedAction::class === \Filament\Actions\CreateAction::class)) {
                            $createAction = $groupedAction;
                            break 2;
                        }
                    }

                    continue;
                }

                if ($action instanceof Action && ($action->getName() === 'create' || $action::class === \Filament\Actions\CreateAction::class)) {
                    $createAction = $action;
                    break;
                }
            }

            if ($createAction instanceof Action) {
                $emptyStateAction = clone $createAction;
                $emptyStateAction
                    ->label(__('app.empty_states.action', ['label' => $singularLabel]))
                    ->icon(Heroicon::Plus)
                    ->color('primary');

                $table
                    ->emptyStateHeading(__('app.empty_states.heading', ['label' => $pluralLabel]))
                    ->emptyStateDescription(__('app.empty_states.description', ['label' => $singularLabel]))
                    ->emptyStateIcon(Heroicon::OutlinedDocumentPlus)
                    ->emptyStateActions([$emptyStateAction]);
            }

            return $table;
        });
    }

    /**
     * Configure the Filament admin panel.
     *
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $panel
            ->default()
            ->id('app')
            ->domain("app.{$host}")
            ->homeUrl(fn (): string => Dashboard::getUrl())
            ->brandName(brand_name())
            ->login(Login::class)
            ->registration(Register::class)
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->passwordReset()
            ->emailVerification()
            ->strictAuthorization()
            ->databaseNotifications()
            ->brandLogoHeight('2.6rem')
            ->brandLogo(fn (): View|Factory => view('filament.app.logo'))
            ->viteTheme('resources/css/app.css')
            ->colors([
                'primary' => [
                    50 => 'oklch(0.969 0.016 293.756)',
                    100 => 'oklch(0.943 0.028 294.588)',
                    200 => 'oklch(0.894 0.055 293.283)',
                    300 => 'oklch(0.811 0.101 293.571)',
                    400 => 'oklch(0.709 0.159 293.541)',
                    500 => 'oklch(0.606 0.219 292.717)',
                    600 => 'oklch(0.541 0.247 293.009)',
                    700 => 'oklch(0.491 0.241 292.581)',
                    800 => 'oklch(0.432 0.211 292.759)',
                    900 => 'oklch(0.380 0.178 293.745)',
                    950 => 'oklch(0.283 0.135 291.089)',
                    'DEFAULT' => 'oklch(0.541 0.247 293.009)',
                ],
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->font('Inter')
            ->userMenuItems([
                Action::make('profile')
                    ->label('Profile')
                    ->icon('heroicon-m-user-circle')
                    ->url(fn (): string => $this->shouldRegisterMenuItem()
                        ? url(EditProfile::getUrl())
                        : url($panel->getPath())),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                CrmStatsOverview::class,
                PipelinePerformanceChart::class,
                LeadTrendChart::class,
                QuickActions::class,
                RecentActivity::class,
            ])
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->pages([
                EditProfile::class,
                ApiTokens::class,
            ])
            ->spa()
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('app.labels.tasks'))
                    ->icon('heroicon-o-shopping-cart'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware(
                [
                    ApplyTenantScopes::class,
                ],
                isPersistent: true
            )
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentApexChartsPlugin::make(),
                CustomFieldsPlugin::make()
                    ->authorize(fn () => Gate::check('update', Filament::getTenant())),
                FeatureFlagPlugin::make()->authorize(function (): bool {
                    try {
                        $tenant = Filament::getTenant();
                        $user = Filament::auth()->user();
                    } catch (NoDefaultPanelSetException) {
                        return false;
                    }

                    if ($tenant === null || $user === null) {
                        return false;
                    }

                    return $this->shouldRegisterMenuItem() && $user->hasTeamRole($tenant, 'admin');
                }),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('@env(\'local\')<x-login-link email="manuk.minasyan1@gmail.com" redirect-url="'.url('/').'" />@endenv'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): View|Factory => view('filament.auth.social_login_buttons')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
                fn (): View|Factory => view('filament.auth.social_login_buttons')
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View|Factory => view('filament.app.analytics')
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('<livewire:language-switcher />')
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View|Factory => view('toastmagic.styles')
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): View|Factory => view('toastmagic.scripts')
            );

        if (Features::hasApiFeatures()) {
            $panel->userMenuItems([
                Action::make('api_tokens')
                    ->label('API Tokens')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => $this->shouldRegisterMenuItem()
                        ? url(ApiTokens::getUrl())
                        : url($panel->getPath())),
            ]);
        }

        if (Features::hasTeamFeatures()) {
            $panel
                ->tenant(Team::class, ownershipRelationship: 'team')
                ->tenantRegistration(CreateTeam::class)
                ->tenantProfile(EditTeam::class);
        }

        return $panel;
    }

    public function shouldRegisterMenuItem(): bool
    {
        $hasVerifiedEmail = Auth::user()?->hasVerifiedEmail();

        try {
            return Filament::hasTenancy()
                ? $hasVerifiedEmail && Filament::getTenant()
                : $hasVerifiedEmail;
        } catch (NoDefaultPanelSetException) {
            return false;
        }
    }
}
