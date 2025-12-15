<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\DataIntegrityCheckType;
use App\Services\DataQuality\DataQualityService;
use Filament\Actions;
use Filament\Forms;
use Filament\Pages\Page;

final class DataQualityDashboard extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar-square';
    }

    protected string $view = 'filament.pages.data-quality-dashboard';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_quality');
    }

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.data_quality_dashboard');
    }

    public function getTitle(): string
    {
        return __('app.pages.data_quality_dashboard');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run_integrity_check')
                ->label(__('app.actions.run_integrity_check'))
                ->icon('heroicon-o-shield-check')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('type')
                        ->label(__('app.labels.check_type'))
                        ->options(DataIntegrityCheckType::class)
                        ->required(),

                    Forms\Components\TextInput::make('target_model')
                        ->label(__('app.labels.target_model'))
                        ->placeholder(\App\Models\Company::class),

                    Forms\Components\KeyValue::make('parameters')
                        ->label(__('app.labels.parameters'))
                        ->keyLabel(__('app.labels.parameter'))
                        ->valueLabel(__('app.labels.value')),
                ])
                ->action(function (array $data): void {
                    $dataQualityService = resolve(DataQualityService::class);
                    $check = $dataQualityService->runIntegrityCheck(
                        DataIntegrityCheckType::from($data['type']),
                        $data['target_model'] ?? null,
                        $data['parameters'] ?? [],
                    );

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.integrity_check_started'))
                        ->body(__('app.notifications.integrity_check_started_body', ['id' => $check->id]))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('create_backup')
                ->label(__('app.actions.create_backup'))
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label(__('app.labels.name'))
                        ->required()
                        ->default('Backup ' . now()->format('Y-m-d H:i:s')),

                    Forms\Components\Textarea::make('description')
                        ->label(__('app.labels.description')),

                    Forms\Components\Select::make('type')
                        ->label(__('app.labels.backup_type'))
                        ->options([
                            'full' => __('enums.backup_job_type.full'),
                            'database_only' => __('enums.backup_job_type.database_only'),
                            'files_only' => __('enums.backup_job_type.files_only'),
                        ])
                        ->default('full')
                        ->required(),

                    Forms\Components\TextInput::make('retention_days')
                        ->label(__('app.labels.retention_days'))
                        ->numeric()
                        ->default(30)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $dataQualityService = resolve(DataQualityService::class);
                    $backup = $dataQualityService->createBackup([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'type' => $data['type'],
                        'retention_days' => $data['retention_days'],
                        'async' => true,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.backup_started'))
                        ->body(__('app.notifications.backup_started_body', ['name' => $backup->name]))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('cleanup_data')
                ->label(__('app.actions.cleanup_data'))
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->form([
                    Forms\Components\CheckboxList::make('cleanup_rules')
                        ->label(__('app.labels.cleanup_rules'))
                        ->options([
                            'normalize_phone_numbers' => __('app.labels.normalize_phone_numbers'),
                            'normalize_email_addresses' => __('app.labels.normalize_email_addresses'),
                            'remove_duplicate_spaces' => __('app.labels.remove_duplicate_spaces'),
                            'standardize_country_codes' => __('app.labels.standardize_country_codes'),
                            'clean_website_urls' => __('app.labels.clean_website_urls'),
                            'merge_duplicate_tags' => __('app.labels.merge_duplicate_tags'),
                        ])
                        ->required(),

                    Forms\Components\Toggle::make('dry_run')
                        ->label(__('app.labels.dry_run'))
                        ->helperText(__('app.helpers.dry_run_cleanup'))
                        ->default(true),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $dataQualityService = resolve(DataQualityService::class);

                    $rules = [];
                    foreach ($data['cleanup_rules'] as $ruleType) {
                        $rules[] = [
                            'type' => $ruleType,
                            'name' => __('app.labels.' . $ruleType),
                            'dry_run' => $data['dry_run'],
                            'table' => $this->getTableForRule($ruleType),
                        ];
                    }

                    $results = $dataQualityService->cleanupData($rules);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.data_cleanup_completed'))
                        ->body(__('app.notifications.data_cleanup_body', [
                            'cleaned' => $results['total_cleaned'],
                            'operations' => count($results['operations']),
                        ]))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Get the quality metrics for display.
     */
    public function getQualityMetrics(): array
    {
        $dataQualityService = resolve(DataQualityService::class);

        return $dataQualityService->getQualityMetrics();
    }

    /**
     * Get the table name for a cleanup rule.
     */
    private function getTableForRule(string $ruleType): string
    {
        return match ($ruleType) {
            'normalize_phone_numbers' => 'people',
            'normalize_email_addresses' => 'people',
            'remove_duplicate_spaces' => 'companies',
            'standardize_country_codes' => 'companies',
            'clean_website_urls' => 'companies',
            'merge_duplicate_tags' => 'tags',
            default => 'companies',
        };
    }
}
