<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Dgtlss\Warden\Services\WardenService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class SecurityAudit extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.pages.security-audit';

    protected static ?int $navigationSort = 100;

    public ?array $auditResult = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.security_audit');
    }

    public function getTitle(): string
    {
        return __('app.labels.security_audit');
    }

    public function mount(): void
    {
        $this->loadLastAuditResult();
    }

    private function loadLastAuditResult(): void
    {
        try {
            $warden = resolve(WardenService::class);
            $result = $warden->getLastAuditResult();

            if ($result) {
                $this->auditResult = [
                    'has_vulnerabilities' => $result->hasVulnerabilities(),
                    'vulnerability_count' => $result->getVulnerabilityCount(),
                    'packages_audited' => $result->getPackagesAudited(),
                    'last_audit' => $result->getAuditTimestamp()?->diffForHumans(),
                    'vulnerabilities' => $result->getVulnerabilities(),
                ];
            }
        } catch (\Exception) {
            $this->auditResult = null;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runAudit')
                ->label(__('app.actions.run_audit'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function (WardenService $warden): void {
                    try {
                        $result = $warden->runAudit(skipCache: true);

                        $this->loadLastAuditResult();

                        if ($result->hasVulnerabilities()) {
                            Notification::make()
                                ->title(__('app.notifications.vulnerabilities_found'))
                                ->body(__('app.notifications.vulnerabilities_count', [
                                    'count' => $result->getVulnerabilityCount(),
                                ]))
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('app.notifications.no_vulnerabilities'))
                                ->body(__('app.notifications.all_dependencies_secure'))
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.audit_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.run_security_audit'))
                ->modalDescription(__('app.modals.run_security_audit_description'))
                ->modalSubmitActionLabel(__('app.actions.run_audit')),

            Action::make('viewHistory')
                ->label(__('app.actions.view_history'))
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn (): string => route('filament.app.pages.security-audit-history'))
                ->visible(fn () => config('warden.history.enabled', false)),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_security_audit') ?? false;
    }
}
