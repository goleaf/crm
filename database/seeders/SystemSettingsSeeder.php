<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

final class SystemSettingsSeeder extends Seeder
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function run(): void
    {
        // Company Information
        $this->settingsService->set('company.name', config('app.name'), 'string', 'company');
        $this->settingsService->set('company.legal_name', '', 'string', 'company');
        $this->settingsService->set('company.tax_id', '', 'string', 'company');
        $this->settingsService->set('company.address', '', 'string', 'company');
        $this->settingsService->set('company.phone', '', 'string', 'company');
        $this->settingsService->set('company.email', '', 'string', 'company');
        $this->settingsService->set('company.website', '', 'string', 'company');
        $this->settingsService->set('company.logo_url', '', 'string', 'company');

        // Locale Settings
        $this->settingsService->set('locale.language', config('app.locale', 'en'), 'string', 'locale');
        $this->settingsService->set('locale.timezone', config('app.timezone', 'UTC'), 'string', 'locale');
        $this->settingsService->set('locale.date_format', 'Y-m-d', 'string', 'locale');
        $this->settingsService->set('locale.time_format', 'H:i:s', 'string', 'locale');
        $this->settingsService->set('locale.first_day_of_week', 0, 'integer', 'locale');

        // Currency Settings
        $this->settingsService->set('currency.default', config('company.default_currency', 'USD'), 'string', 'currency');
        $this->settingsService->set('currency.exchange_rates', [], 'array', 'currency');
        $this->settingsService->set('currency.auto_update_rates', false, 'boolean', 'currency');

        // Fiscal Year Settings
        $this->settingsService->set('fiscal.start_month', 1, 'integer', 'fiscal');
        $this->settingsService->set('fiscal.start_day', 1, 'integer', 'fiscal');

        // Business Hours (Monday-Friday 9-5)
        $defaultHours = ['start' => '09:00', 'end' => '17:00'];
        $this->settingsService->set('business_hours.monday', $defaultHours, 'array', 'business_hours');
        $this->settingsService->set('business_hours.tuesday', $defaultHours, 'array', 'business_hours');
        $this->settingsService->set('business_hours.wednesday', $defaultHours, 'array', 'business_hours');
        $this->settingsService->set('business_hours.thursday', $defaultHours, 'array', 'business_hours');
        $this->settingsService->set('business_hours.friday', $defaultHours, 'array', 'business_hours');
        $this->settingsService->set('business_hours.saturday', null, 'string', 'business_hours');
        $this->settingsService->set('business_hours.sunday', null, 'string', 'business_hours');

        // Holidays (empty by default)
        $this->settingsService->set('business_hours.holidays', [], 'array', 'business_hours');

        // Email Settings
        $this->settingsService->set('email.from_address', config('mail.from.address'), 'string', 'email');
        $this->settingsService->set('email.from_name', config('mail.from.name'), 'string', 'email');
        $this->settingsService->set('email.reply_to', '', 'string', 'email');

        // Notification Defaults
        $this->settingsService->set('notifications.email_enabled', true, 'boolean', 'notification');
        $this->settingsService->set('notifications.database_enabled', true, 'boolean', 'notification');
        $this->settingsService->set('notifications.slack_enabled', false, 'boolean', 'notification');
        $this->settingsService->set('notifications.slack_webhook', '', 'string', 'notification');

        // Scheduler/Cron Settings
        $this->settingsService->set('scheduler.enabled', true, 'boolean', 'scheduler');
        $this->settingsService->set('scheduler.timezone', config('app.timezone', 'UTC'), 'string', 'scheduler');
    }
}
