<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\DatabaseOptimizationService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Throwable;

final class DatabaseOptimization extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 1040;

    protected string $view = 'filament.pages.database-optimization';

    public ?array $status = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.database_optimization');
    }

    public function getTitle(): string
    {
        return __('app.navigation.database_optimization');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user?->hasVerifiedEmail()
            && ($user->ownsTeam($tenant) || $user->hasTeamRole($tenant, 'admin'));
    }

    public function mount(DatabaseOptimizationService $service): void
    {
        $this->status = $service->status();
    }

    public function refreshStatus(DatabaseOptimizationService $service): void
    {
        $this->status = $service->status();

        Notification::make()
            ->title(__('app.notifications.database_optimization_refreshed'))
            ->success()
            ->send();
    }

    public function publishOptimizationMigration(DatabaseOptimizationService $service): void
    {
        try {
            $path = $service->ensureMigrationPublished();

            $this->status = $service->status();

            Notification::make()
                ->title(__('app.notifications.database_optimization_published'))
                ->body($path ? __('app.messages.optimization_migration_published', ['path' => $path]) : null)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('app.notifications.database_optimization_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    #[Computed]
    public function runtimePragmas(): array
    {
        return $this->status['pragmas'] ?? [];
    }

    #[Computed]
    public function isSqlite(): bool
    {
        return ($this->status['driver'] ?? null) === 'sqlite';
    }
}
