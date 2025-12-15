<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\PhpInsightsService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

final class PhpInsights extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1020;

    protected string $view = 'filament.pages.phpinsights';

    /**
     * @var array{summary: array<string, mixed>, issues: array<string, array<int, array<string, mixed>>>}
     */
    public array $report = [
        'summary' => [],
        'issues' => [],
    ];

    public function mount(PhpInsightsService $service): void
    {
        $this->refreshReport($service, notify: false);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.phpinsights');
    }

    public function getTitle(): string
    {
        return __('app.navigation.phpinsights');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user?->hasVerifiedEmail()
            && ($user->ownsTeam($tenant) || $user->hasTeamRole($tenant, 'admin'));
    }

    public function refreshReport(?PhpInsightsService $service = null, bool $notify = true): void
    {
        $service ??= resolve(PhpInsightsService::class);

        try {
            $this->report = $service->analyze();

            if ($notify) {
                Notification::make()
                    ->title(__('app.notifications.insights_refreshed'))
                    ->success()
                    ->send();
            }
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('app.notifications.insights_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<int, array{key: string, label: string, value: float|null, color: string, barColor: string, progress: float}>
     */
    #[\Livewire\Attributes\Computed]
    public function summaryCards(): array
    {
        $summary = $this->report['summary'] ?? [];

        $scores = [
            'code' => __('app.labels.code_quality'),
            'complexity' => __('app.labels.complexity'),
            'architecture' => __('app.labels.architecture'),
            'style' => __('app.labels.style'),
        ];

        return collect($scores)
            ->map(function (string $label, string $key) use ($summary): array {
                $value = $summary[$key] ?? null;
                $progress = $value !== null ? (float) max(min((float) $value, 100), 0) : 0.0;
                $color = $this->scoreColor($value);

                return [
                    'key' => $key,
                    'label' => $label,
                    'value' => $value !== null ? (float) $value : null,
                    'color' => $color,
                    'barColor' => $this->barColor($color),
                    'progress' => $progress,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    #[\Livewire\Attributes\Computed]
    public function issueGroups(): array
    {
        $issues = $this->report['issues'] ?? [];

        return [
            [
                'key' => 'code',
                'label' => __('app.labels.code_quality'),
                'items' => $issues['code'] ?? [],
            ],
            [
                'key' => 'complexity',
                'label' => __('app.labels.complexity'),
                'items' => $issues['complexity'] ?? [],
            ],
            [
                'key' => 'architecture',
                'label' => __('app.labels.architecture'),
                'items' => $issues['architecture'] ?? [],
            ],
            [
                'key' => 'style',
                'label' => __('app.labels.style'),
                'items' => $issues['style'] ?? [],
            ],
            [
                'key' => 'security',
                'label' => __('app.labels.security'),
                'items' => $issues['security'] ?? [],
            ],
        ];
    }

    private function scoreColor(?float $score): string
    {
        if ($score === null) {
            return 'gray';
        }

        return match (true) {
            $score >= 90 => 'success',
            $score >= 75 => 'info',
            $score >= 60 => 'warning',
            default => 'danger',
        };
    }

    private function barColor(string $color): string
    {
        return match ($color) {
            'success' => 'bg-success-500 dark:bg-success-400',
            'info' => 'bg-primary-500 dark:bg-primary-400',
            'warning' => 'bg-warning-500 dark:bg-warning-400',
            'danger' => 'bg-danger-500 dark:bg-danger-400',
            default => 'bg-gray-400 dark:bg-gray-500',
        };
    }
}
