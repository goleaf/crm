<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;

final class PailLogs extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 1030;

    protected string $view = 'filament.pages.pail-logs';

    public bool $pcntlAvailable = false;

    public function mount(): void
    {
        $this->pcntlAvailable = extension_loaded('pcntl');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.pail');
    }

    public function getTitle(): string
    {
        return __('app.navigation.pail');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user?->hasVerifiedEmail()
            && ($user->ownsTeam($tenant) || $user->hasTeamRole($tenant, 'admin'));
    }

    /**
     * @return array<int, array{label: string, command: string, description: string}>
     */
    #[\Livewire\Attributes\Computed]
    public function commands(): array
    {
        return [
            [
                'label' => __('app.pail.commands.tail_all'),
                'command' => 'php artisan pail',
                'description' => __('app.pail.descriptions.tail_all'),
            ],
            [
                'label' => __('app.pail.commands.verbose'),
                'command' => 'php artisan pail -vv',
                'description' => __('app.pail.descriptions.verbose'),
            ],
            [
                'label' => __('app.pail.commands.long_running'),
                'command' => 'php artisan pail --timeout=0',
                'description' => __('app.pail.descriptions.long_running'),
            ],
            [
                'label' => __('app.pail.commands.level_and_user'),
                'command' => 'php artisan pail --level=error --user=1',
                'description' => __('app.pail.descriptions.level_and_user'),
            ],
            [
                'label' => __('app.pail.commands.message_filter'),
                'command' => 'php artisan pail --message="User created"',
                'description' => __('app.pail.descriptions.message_filter'),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, example: string, description: string}>
     */
    #[\Livewire\Attributes\Computed]
    public function filters(): array
    {
        return [
            [
                'label' => '--filter',
                'example' => 'php artisan pail --filter="QueryException"',
                'description' => __('app.pail.filters.filter'),
            ],
            [
                'label' => '--message',
                'example' => 'php artisan pail --message="Webhook received"',
                'description' => __('app.pail.filters.message'),
            ],
            [
                'label' => '--level',
                'example' => 'php artisan pail --level=warning',
                'description' => __('app.pail.filters.level'),
            ],
            [
                'label' => '--user / --auth',
                'example' => 'php artisan pail --user=42',
                'description' => __('app.pail.filters.user'),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    #[\Livewire\Attributes\Computed]
    public function tips(): array
    {
        return [
            __('app.pail.tips.timeout'),
            __('app.pail.tips.verbosity'),
            __('app.pail.tips.drivers'),
            __('app.pail.tips.filters'),
        ];
    }
}
