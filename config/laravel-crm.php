<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Owner Settings
    |--------------------------------------------------------------------------
    |
    | Configure the default owner and ownership behavior for CRM records.
    |
    */
    'owner' => [
        'default_owner_id' => env('CRM_DEFAULT_OWNER_ID'),
        'auto_assign_on_create' => env('CRM_AUTO_ASSIGN_OWNER', true),
        'allow_owner_change' => env('CRM_ALLOW_OWNER_CHANGE', true),
        'notify_on_assignment' => env('CRM_NOTIFY_ON_ASSIGNMENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the route prefix and middleware for CRM routes.
    |
    */
    'routes' => [
        'prefix' => env('CRM_ROUTE_PREFIX', 'crm'),
        'middleware' => ['web', 'auth'],
        'domain' => env('CRM_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database table prefixes and connection settings.
    |
    */
    'database' => [
        'table_prefix' => env('CRM_TABLE_PREFIX', ''),
        'connection' => env('CRM_DB_CONNECTION', config('database.default')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific CRM features.
    |
    */
    'features' => [
        'companies' => env('CRM_FEATURE_COMPANIES', true),
        'people' => env('CRM_FEATURE_PEOPLE', true),
        'opportunities' => env('CRM_FEATURE_OPPORTUNITIES', true),
        'tasks' => env('CRM_FEATURE_TASKS', true),
        'notes' => env('CRM_FEATURE_NOTES', true),
        'leads' => env('CRM_FEATURE_LEADS', true),
        'support_cases' => env('CRM_FEATURE_SUPPORT_CASES', true),
        'deliveries' => env('CRM_FEATURE_DELIVERIES', true),
        'projects' => env('CRM_FEATURE_PROJECTS', true),
        'custom_fields' => env('CRM_FEATURE_CUSTOM_FIELDS', true),
        'exports' => env('CRM_FEATURE_EXPORTS', true),
        'imports' => env('CRM_FEATURE_IMPORTS', true),
        'kanban_boards' => env('CRM_FEATURE_KANBAN_BOARDS', true),
        'activity_log' => env('CRM_FEATURE_ACTIVITY_LOG', true),
        'file_attachments' => env('CRM_FEATURE_FILE_ATTACHMENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Customization
    |--------------------------------------------------------------------------
    |
    | Customize the look and feel of the CRM interface.
    |
    */
    'ui' => [
        'theme' => env('CRM_THEME', 'default'),
        'primary_color' => env('CRM_PRIMARY_COLOR', 'blue'),
        'logo_url' => env('CRM_LOGO_URL'),
        'favicon_url' => env('CRM_FAVICON_URL'),
        'brand_name' => env('CRM_BRAND_NAME', config('app.name')),
        'command_prefix' => env('CRM_COMMAND_PREFIX'),
        'logo_asset' => env('CRM_LOGO_ASSET', 'crm-logo.svg'),
        'logomark_asset' => env('CRM_LOGOMARK_ASSET', 'crm-logomark.svg'),
        'logo_white_asset' => env('CRM_LOGO_WHITE_ASSET', 'images/crm-logo-white.png'),
        'github_owner' => env('CRM_GITHUB_OWNER'),
        'github_repo' => env('CRM_GITHUB_REPO'),
        'github_url' => env('CRM_SOCIAL_GITHUB_URL'),
        'social' => [
            'github' => env('CRM_SOCIAL_GITHUB_URL'),
            'x' => env('CRM_SOCIAL_X_URL'),
            'linkedin' => env('CRM_SOCIAL_LINKEDIN_URL'),
        ],
        'items_per_page' => env('CRM_ITEMS_PER_PAGE', 25),
        'date_format' => env('CRM_DATE_FORMAT', 'Y-m-d'),
        'time_format' => env('CRM_TIME_FORMAT', 'H:i:s'),
        'datetime_format' => env('CRM_DATETIME_FORMAT', 'Y-m-d H:i:s'),
        'enable_dark_mode' => env('CRM_ENABLE_DARK_MODE', true),
        'default_theme_mode' => env('CRM_DEFAULT_THEME_MODE', 'light'), // light, dark, system
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configure third-party integrations and API settings.
    |
    */
    'integrations' => [
        'google' => [
            'enabled' => env('CRM_GOOGLE_INTEGRATION_ENABLED', false),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        ],
        'microsoft' => [
            'enabled' => env('CRM_MICROSOFT_INTEGRATION_ENABLED', false),
            'client_id' => env('MICROSOFT_CLIENT_ID'),
            'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
            'redirect_uri' => env('MICROSOFT_REDIRECT_URI'),
        ],
        'slack' => [
            'enabled' => env('CRM_SLACK_INTEGRATION_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#crm'),
        ],
        'zapier' => [
            'enabled' => env('CRM_ZAPIER_INTEGRATION_ENABLED', false),
            'api_key' => env('ZAPIER_API_KEY'),
        ],
        'api' => [
            'enabled' => env('CRM_API_ENABLED', true),
            'rate_limit' => env('CRM_API_RATE_LIMIT', 60),
            'rate_limit_period' => env('CRM_API_RATE_LIMIT_PERIOD', 1), // minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure email templates and notification settings.
    |
    */
    'email' => [
        'from_address' => env('CRM_MAIL_FROM_ADDRESS', config('mail.from.address')),
        'from_name' => env('CRM_MAIL_FROM_NAME', config('mail.from.name')),
        'reply_to_address' => env('CRM_MAIL_REPLY_TO_ADDRESS'),
        'reply_to_name' => env('CRM_MAIL_REPLY_TO_NAME'),
        'templates' => [
            'opportunity_created' => 'emails.opportunity-created',
            'task_assigned' => 'emails.task-assigned',
            'task_due_reminder' => 'emails.task-due-reminder',
            'lead_assigned' => 'emails.lead-assigned',
            'support_case_created' => 'emails.support-case-created',
            'support_case_updated' => 'emails.support-case-updated',
        ],
        'notifications' => [
            'opportunity_created' => env('CRM_NOTIFY_OPPORTUNITY_CREATED', true),
            'opportunity_won' => env('CRM_NOTIFY_OPPORTUNITY_WON', true),
            'opportunity_lost' => env('CRM_NOTIFY_OPPORTUNITY_LOST', false),
            'task_assigned' => env('CRM_NOTIFY_TASK_ASSIGNED', true),
            'task_due_soon' => env('CRM_NOTIFY_TASK_DUE_SOON', true),
            'task_overdue' => env('CRM_NOTIFY_TASK_OVERDUE', true),
            'lead_assigned' => env('CRM_NOTIFY_LEAD_ASSIGNED', true),
            'support_case_created' => env('CRM_NOTIFY_SUPPORT_CASE_CREATED', true),
            'support_case_resolved' => env('CRM_NOTIFY_SUPPORT_CASE_RESOLVED', true),
        ],
        'digest' => [
            'enabled' => env('CRM_EMAIL_DIGEST_ENABLED', false),
            'frequency' => env('CRM_EMAIL_DIGEST_FREQUENCY', 'daily'), // daily, weekly, monthly
            'time' => env('CRM_EMAIL_DIGEST_TIME', '08:00'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Settings
    |--------------------------------------------------------------------------
    |
    | Configure localization and regional settings.
    |
    */
    'locale' => [
        'default' => env('CRM_LOCALE', config('app.locale', 'en')),
        'fallback' => env('CRM_FALLBACK_LOCALE', config('app.fallback_locale', 'en')),
        'available' => ['en', 'uk'],
        'timezone' => env('CRM_TIMEZONE', config('app.timezone', 'UTC')),
        'first_day_of_week' => env('CRM_FIRST_DAY_OF_WEEK', 0), // 0 = Sunday, 1 = Monday
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Settings
    |--------------------------------------------------------------------------
    |
    | Default company information and business settings.
    |
    */
    'company' => [
        'name' => env('CRM_COMPANY_NAME', config('app.name')),
        'legal_name' => env('CRM_COMPANY_LEGAL_NAME'),
        'tax_id' => env('CRM_COMPANY_TAX_ID'),
        'address' => [
            'street' => env('CRM_COMPANY_ADDRESS_STREET'),
            'city' => env('CRM_COMPANY_ADDRESS_CITY'),
            'state' => env('CRM_COMPANY_ADDRESS_STATE'),
            'postal_code' => env('CRM_COMPANY_ADDRESS_POSTAL_CODE'),
            'country' => env('CRM_COMPANY_ADDRESS_COUNTRY'),
        ],
        'contact' => [
            'phone' => env('CRM_COMPANY_PHONE'),
            'email' => env('CRM_COMPANY_EMAIL'),
            'website' => env('CRM_COMPANY_WEBSITE'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Configure currency and financial settings.
    |
    */
    'currency' => [
        'default' => env('CRM_DEFAULT_CURRENCY', 'USD'),
        'symbol' => env('CRM_CURRENCY_SYMBOL', '$'),
        'position' => env('CRM_CURRENCY_POSITION', 'before'), // before, after
        'decimal_separator' => env('CRM_DECIMAL_SEPARATOR', '.'),
        'thousands_separator' => env('CRM_THOUSANDS_SEPARATOR', ','),
        'decimal_places' => env('CRM_DECIMAL_PLACES', 2),
        'exchange_rates' => [
            'auto_update' => env('CRM_AUTO_UPDATE_EXCHANGE_RATES', false),
            'provider' => env('CRM_EXCHANGE_RATE_PROVIDER', 'fixer'), // fixer, openexchangerates
            'api_key' => env('CRM_EXCHANGE_RATE_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fiscal Year Settings
    |--------------------------------------------------------------------------
    |
    | Configure fiscal year start date for reporting.
    |
    */
    'fiscal_year' => [
        'start_month' => env('CRM_FISCAL_YEAR_START_MONTH', 1), // 1-12
        'start_day' => env('CRM_FISCAL_YEAR_START_DAY', 1), // 1-31
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours
    |--------------------------------------------------------------------------
    |
    | Define business hours for SLA calculations and scheduling.
    |
    */
    'business_hours' => [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, // null = closed
        'sunday' => null,
        'holidays' => [], // Array of Y-m-d dates
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Settings
    |--------------------------------------------------------------------------
    |
    | Configure task management defaults.
    |
    */
    'tasks' => [
        'default_priority' => env('CRM_TASK_DEFAULT_PRIORITY', 'medium'),
        'default_status' => env('CRM_TASK_DEFAULT_STATUS', 'pending'),
        'auto_assign_to_creator' => env('CRM_TASK_AUTO_ASSIGN_TO_CREATOR', true),
        'reminder_before_due' => env('CRM_TASK_REMINDER_BEFORE_DUE', 24), // hours
        'overdue_notification_frequency' => env('CRM_TASK_OVERDUE_NOTIFICATION_FREQUENCY', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Opportunity Settings
    |--------------------------------------------------------------------------
    |
    | Configure opportunity pipeline defaults.
    |
    */
    'opportunities' => [
        'default_stage' => env('CRM_OPPORTUNITY_DEFAULT_STAGE', 'qualification'),
        'default_probability' => env('CRM_OPPORTUNITY_DEFAULT_PROBABILITY', 10),
        'auto_calculate_probability' => env('CRM_OPPORTUNITY_AUTO_CALCULATE_PROBABILITY', true),
        'require_close_reason' => env('CRM_OPPORTUNITY_REQUIRE_CLOSE_REASON', true),
        'aging_threshold_days' => env('CRM_OPPORTUNITY_AGING_THRESHOLD_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Settings
    |--------------------------------------------------------------------------
    |
    | Configure lead management defaults.
    |
    */
    'leads' => [
        'default_status' => env('CRM_LEAD_DEFAULT_STATUS', 'new'),
        'default_source' => env('CRM_LEAD_DEFAULT_SOURCE', 'website'),
        'auto_assign_enabled' => env('CRM_LEAD_AUTO_ASSIGN_ENABLED', false),
        'auto_assign_method' => env('CRM_LEAD_AUTO_ASSIGN_METHOD', 'round_robin'), // round_robin, least_busy
        'qualification_required' => env('CRM_LEAD_QUALIFICATION_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Case Settings
    |--------------------------------------------------------------------------
    |
    | Configure support case management defaults.
    |
    */
    'support_cases' => [
        'default_priority' => env('CRM_CASE_DEFAULT_PRIORITY', 'medium'),
        'default_status' => env('CRM_CASE_DEFAULT_STATUS', 'open'),
        'auto_assign_enabled' => env('CRM_CASE_AUTO_ASSIGN_ENABLED', false),
        'sla_enabled' => env('CRM_CASE_SLA_ENABLED', true),
        'sla_response_time' => [ // in hours
            'critical' => 1,
            'high' => 4,
            'medium' => 24,
            'low' => 48,
        ],
        'sla_resolution_time' => [ // in hours
            'critical' => 4,
            'high' => 24,
            'medium' => 72,
            'low' => 168,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configure file upload limits and allowed types.
    |
    */
    'uploads' => [
        'max_file_size' => env('CRM_MAX_FILE_SIZE', 10240), // KB
        'allowed_extensions' => [
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
        ],
        'disk' => env('CRM_UPLOAD_DISK', 'local'),
        'path' => env('CRM_UPLOAD_PATH', 'crm-uploads'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security and access control settings.
    |
    */
    'security' => [
        'require_2fa' => env('CRM_REQUIRE_2FA', false),
        'session_timeout' => env('CRM_SESSION_TIMEOUT', 120), // minutes
        'password_expiry_days' => env('CRM_PASSWORD_EXPIRY_DAYS', 90),
        'max_login_attempts' => env('CRM_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('CRM_LOCKOUT_DURATION', 15), // minutes
        'audit_log_enabled' => env('CRM_AUDIT_LOG_ENABLED', true),
        'ip_whitelist' => env('CRM_IP_WHITELIST') ? explode(',', (string) env('CRM_IP_WHITELIST')) : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for CRM data.
    |
    */
    'cache' => [
        'enabled' => env('CRM_CACHE_ENABLED', true),
        'ttl' => env('CRM_CACHE_TTL', 3600), // seconds
        'driver' => env('CRM_CACHE_DRIVER', config('cache.default')),
        'prefix' => env('CRM_CACHE_PREFIX', 'crm'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Configure search functionality.
    |
    */
    'search' => [
        'driver' => env('CRM_SEARCH_DRIVER', 'database'), // database, meilisearch, algolia
        'min_characters' => env('CRM_SEARCH_MIN_CHARACTERS', 2),
        'results_per_page' => env('CRM_SEARCH_RESULTS_PER_PAGE', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Settings
    |--------------------------------------------------------------------------
    |
    | Configure AI-powered features.
    |
    */
    'ai' => [
        'enabled' => env('CRM_AI_ENABLED', false),
        'provider' => env('CRM_AI_PROVIDER', 'anthropic'), // anthropic
        'api_key' => env('CRM_AI_API_KEY'),
        'model' => env('CRM_AI_MODEL', 'claude-3-haiku-20240307'),
        'max_tokens' => env('CRM_AI_MAX_TOKENS', 500),
        'temperature' => env('CRM_AI_TEMPERATURE', 0.7),
    ],
];
