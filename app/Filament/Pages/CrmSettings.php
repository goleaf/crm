<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class CrmSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.crm-settings';

    protected static ?int $navigationSort = 1000;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getSettingsData());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.crm_settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        $this->getCompanyTab(),
                        $this->getLocaleTab(),
                        $this->getCurrencyTab(),
                        $this->getBusinessHoursTab(),
                        $this->getEmailTab(),
                        $this->getNotificationsTab(),
                        $this->getFeaturesTab(),
                        $this->getSecurityTab(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    private function getCompanyTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.company'))
            ->icon('heroicon-o-building-office')
            ->schema([
                Forms\Components\Section::make(__('app.sections.company_information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('company.name')
                                    ->label(__('app.labels.company_name'))
                                    ->required(),

                                Forms\Components\TextInput::make('company.legal_name')
                                    ->label(__('app.labels.legal_name')),

                                Forms\Components\TextInput::make('company.tax_id')
                                    ->label(__('app.labels.tax_id')),

                                Forms\Components\TextInput::make('company.email')
                                    ->label(__('app.labels.email'))
                                    ->email(),

                                Forms\Components\TextInput::make('company.phone')
                                    ->label(__('app.labels.phone'))
                                    ->tel(),

                                Forms\Components\TextInput::make('company.website')
                                    ->label(__('app.labels.website'))
                                    ->url(),
                            ]),

                        Forms\Components\Textarea::make('company.address')
                            ->label(__('app.labels.address'))
                            ->rows(3),

                        Forms\Components\FileUpload::make('company.logo_url')
                            ->label(__('app.labels.logo'))
                            ->image()
                            ->maxSize(2048),
                    ]),
            ]);
    }

    private function getLocaleTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.locale'))
            ->icon('heroicon-o-language')
            ->schema([
                Forms\Components\Section::make(__('app.sections.locale_settings'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('locale.language')
                                    ->label(__('app.labels.language'))
                                    ->options([
                                        'en' => 'English',
                                        'uk' => 'Українська',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('locale.timezone')
                                    ->label(__('app.labels.timezone'))
                                    ->options(collect(timezone_identifiers_list())->mapWithKeys(fn ($tz): array => [$tz => $tz]))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('locale.date_format')
                                    ->label(__('app.labels.date_format'))
                                    ->helperText('e.g., Y-m-d, d/m/Y, m/d/Y')
                                    ->required(),

                                Forms\Components\TextInput::make('locale.time_format')
                                    ->label(__('app.labels.time_format'))
                                    ->helperText('e.g., H:i:s, h:i A')
                                    ->required(),

                                Forms\Components\Select::make('locale.first_day_of_week')
                                    ->label(__('app.labels.first_day_of_week'))
                                    ->options([
                                        0 => 'Sunday',
                                        1 => 'Monday',
                                        2 => 'Tuesday',
                                        3 => 'Wednesday',
                                        4 => 'Thursday',
                                        5 => 'Friday',
                                        6 => 'Saturday',
                                    ])
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    private function getCurrencyTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.currency'))
            ->icon('heroicon-o-currency-dollar')
            ->schema([
                Forms\Components\Section::make(__('app.sections.currency_settings'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('currency.default')
                                    ->label(__('app.labels.default_currency'))
                                    ->required()
                                    ->maxLength(3),

                                Forms\Components\TextInput::make('currency.symbol')
                                    ->label(__('app.labels.currency_symbol'))
                                    ->required(),

                                Forms\Components\Select::make('currency.position')
                                    ->label(__('app.labels.symbol_position'))
                                    ->options([
                                        'before' => 'Before ($100)',
                                        'after' => 'After (100$)',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('currency.decimal_places')
                                    ->label(__('app.labels.decimal_places'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(4)
                                    ->required(),

                                Forms\Components\TextInput::make('currency.decimal_separator')
                                    ->label(__('app.labels.decimal_separator'))
                                    ->required()
                                    ->maxLength(1),

                                Forms\Components\TextInput::make('currency.thousands_separator')
                                    ->label(__('app.labels.thousands_separator'))
                                    ->maxLength(1),
                            ]),

                        Forms\Components\Toggle::make('currency.exchange_rates.auto_update')
                            ->label(__('app.labels.auto_update_exchange_rates')),
                    ]),
            ]);
    }

    private function getBusinessHoursTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.business_hours'))
            ->icon('heroicon-o-clock')
            ->schema([
                Forms\Components\Section::make(__('app.sections.business_hours'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                $this->getBusinessHoursField('monday', 'Monday'),
                                $this->getBusinessHoursField('tuesday', 'Tuesday'),
                                $this->getBusinessHoursField('wednesday', 'Wednesday'),
                                $this->getBusinessHoursField('thursday', 'Thursday'),
                                $this->getBusinessHoursField('friday', 'Friday'),
                                $this->getBusinessHoursField('saturday', 'Saturday'),
                                $this->getBusinessHoursField('sunday', 'Sunday'),
                            ]),
                    ]),
            ]);
    }

    private function getBusinessHoursField(string $day, string $label): Forms\Components\Group
    {
        return Forms\Components\Group::make()
            ->schema([
                Forms\Components\Toggle::make("business_hours.{$day}.enabled")
                    ->label($label)
                    ->live(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TimePicker::make("business_hours.{$day}.start")
                            ->label('Start')
                            ->visible(fn (Forms\Get $get) => $get("business_hours.{$day}.enabled")),

                        Forms\Components\TimePicker::make("business_hours.{$day}.end")
                            ->label('End')
                            ->visible(fn (Forms\Get $get) => $get("business_hours.{$day}.enabled")),
                    ]),
            ]);
    }

    private function getEmailTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.email'))
            ->icon('heroicon-o-envelope')
            ->schema([
                Forms\Components\Section::make(__('app.sections.email_settings'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email.from_address')
                                    ->label(__('app.labels.from_address'))
                                    ->email()
                                    ->required(),

                                Forms\Components\TextInput::make('email.from_name')
                                    ->label(__('app.labels.from_name'))
                                    ->required(),

                                Forms\Components\TextInput::make('email.reply_to_address')
                                    ->label(__('app.labels.reply_to_address'))
                                    ->email(),

                                Forms\Components\TextInput::make('email.reply_to_name')
                                    ->label(__('app.labels.reply_to_name')),
                            ]),
                    ]),
            ]);
    }

    private function getNotificationsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.notifications'))
            ->icon('heroicon-o-bell')
            ->schema([
                Forms\Components\Section::make(__('app.sections.notification_settings'))
                    ->schema([
                        Forms\Components\Toggle::make('email.notifications.opportunity_created')
                            ->label(__('app.labels.notify_opportunity_created')),

                        Forms\Components\Toggle::make('email.notifications.opportunity_won')
                            ->label(__('app.labels.notify_opportunity_won')),

                        Forms\Components\Toggle::make('email.notifications.task_assigned')
                            ->label(__('app.labels.notify_task_assigned')),

                        Forms\Components\Toggle::make('email.notifications.task_due_soon')
                            ->label(__('app.labels.notify_task_due_soon')),

                        Forms\Components\Toggle::make('email.notifications.lead_assigned')
                            ->label(__('app.labels.notify_lead_assigned')),

                        Forms\Components\Toggle::make('email.notifications.support_case_created')
                            ->label(__('app.labels.notify_support_case_created')),
                    ]),
            ]);
    }

    private function getFeaturesTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.features'))
            ->icon('heroicon-o-puzzle-piece')
            ->schema([
                Forms\Components\Section::make(__('app.sections.feature_toggles'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('features.companies')
                                    ->label(__('app.labels.companies')),

                                Forms\Components\Toggle::make('features.people')
                                    ->label(__('app.labels.people')),

                                Forms\Components\Toggle::make('features.opportunities')
                                    ->label(__('app.labels.opportunities')),

                                Forms\Components\Toggle::make('features.tasks')
                                    ->label(__('app.labels.tasks')),

                                Forms\Components\Toggle::make('features.notes')
                                    ->label(__('app.labels.notes')),

                                Forms\Components\Toggle::make('features.leads')
                                    ->label(__('app.labels.leads')),

                                Forms\Components\Toggle::make('features.support_cases')
                                    ->label(__('app.labels.cases')),

                                Forms\Components\Toggle::make('features.ai_summaries')
                                    ->label(__('app.labels.ai_summaries')),

                                Forms\Components\Toggle::make('features.exports')
                                    ->label(__('app.labels.exports')),

                                Forms\Components\Toggle::make('features.imports')
                                    ->label(__('app.labels.imports')),
                            ]),
                    ]),
            ]);
    }

    private function getSecurityTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('app.labels.security'))
            ->icon('heroicon-o-shield-check')
            ->schema([
                Forms\Components\Section::make(__('app.sections.security_settings'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('security.require_2fa')
                                    ->label(__('app.labels.require_2fa')),

                                Forms\Components\Toggle::make('security.audit_log_enabled')
                                    ->label(__('app.labels.audit_log_enabled')),

                                Forms\Components\TextInput::make('security.session_timeout')
                                    ->label(__('app.labels.session_timeout'))
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->required(),

                                Forms\Components\TextInput::make('security.max_login_attempts')
                                    ->label(__('app.labels.max_login_attempts'))
                                    ->numeric()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    private function getSettingsData(): array
    {
        $service = app(SettingsService::class);
        $teamId = auth()->user()?->currentTeam?->id;

        return [
            'company' => $service->getCompanyInfo($teamId),
            'locale' => $service->getLocaleSettings($teamId),
            'currency' => $service->getCurrencySettings($teamId),
            'business_hours' => $service->getBusinessHours($teamId),
            'email' => [
                'from_address' => $service->get('email.from_address', config('mail.from.address'), $teamId),
                'from_name' => $service->get('email.from_name', config('mail.from.name'), $teamId),
                'reply_to_address' => $service->get('email.reply_to_address', null, $teamId),
                'reply_to_name' => $service->get('email.reply_to_name', null, $teamId),
                'notifications' => $service->getNotificationDefaults($teamId),
            ],
            'features' => [
                'companies' => $service->get('features.companies', true, $teamId),
                'people' => $service->get('features.people', true, $teamId),
                'opportunities' => $service->get('features.opportunities', true, $teamId),
                'tasks' => $service->get('features.tasks', true, $teamId),
                'notes' => $service->get('features.notes', true, $teamId),
                'leads' => $service->get('features.leads', true, $teamId),
                'support_cases' => $service->get('features.support_cases', true, $teamId),
                'ai_summaries' => $service->get('features.ai_summaries', false, $teamId),
                'exports' => $service->get('features.exports', true, $teamId),
                'imports' => $service->get('features.imports', true, $teamId),
            ],
            'security' => [
                'require_2fa' => $service->get('security.require_2fa', false, $teamId),
                'audit_log_enabled' => $service->get('security.audit_log_enabled', true, $teamId),
                'session_timeout' => $service->get('security.session_timeout', 120, $teamId),
                'max_login_attempts' => $service->get('security.max_login_attempts', 5, $teamId),
            ],
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $service = app(SettingsService::class);
        $teamId = auth()->user()?->currentTeam?->id;

        // Save company settings
        foreach ($data['company'] ?? [] as $key => $value) {
            $service->set("company.{$key}", $value, $this->inferType($value), 'company', $teamId);
        }

        // Save locale settings
        foreach ($data['locale'] ?? [] as $key => $value) {
            $service->set("locale.{$key}", $value, $this->inferType($value), 'locale', $teamId);
        }

        // Save currency settings
        $this->saveFlattenedSettings($data['currency'] ?? [], 'currency', 'currency', $service, $teamId);

        // Save business hours
        foreach ($data['business_hours'] ?? [] as $day => $hours) {
            $service->set("business_hours.{$day}", $hours, 'array', 'business_hours', $teamId);
        }

        // Save email settings
        $this->saveFlattenedSettings($data['email'] ?? [], 'email', 'email', $service, $teamId);

        // Save feature toggles
        foreach ($data['features'] ?? [] as $key => $value) {
            $service->set("features.{$key}", $value, 'boolean', 'general', $teamId);
        }

        // Save security settings
        foreach ($data['security'] ?? [] as $key => $value) {
            $service->set("security.{$key}", $value, $this->inferType($value), 'general', $teamId);
        }

        Notification::make()
            ->title(__('app.messages.settings_saved'))
            ->success()
            ->send();
    }

    private function saveFlattenedSettings(array $data, string $prefix, string $group, SettingsService $service, ?int $teamId): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->saveFlattenedSettings($value, "{$prefix}.{$key}", $group, $service, $teamId);
            } else {
                $service->set("{$prefix}.{$key}", $value, $this->inferType($value), $group, $teamId);
            }
        }
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }
}
