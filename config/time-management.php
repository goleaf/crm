<?php

declare(strict_types=1);

return [
    'time_entries' => [
        'approval' => [
            'enabled' => (bool) env('TIME_MANAGEMENT_TIME_ENTRY_APPROVAL_ENABLED', false),
            'past_date_cutoff_days' => (int) env('TIME_MANAGEMENT_TIME_ENTRY_PAST_DATE_CUTOFF_DAYS', 0),
            'lock_approved' => (bool) env('TIME_MANAGEMENT_TIME_ENTRY_LOCK_APPROVED', true),
        ],
        'validation' => [
            // Soft threshold used for flagging/reporting (not hard blocking unless a caller enforces it).
            'daily_threshold_minutes' => (int) env('TIME_MANAGEMENT_TIME_ENTRY_DAILY_THRESHOLD_MINUTES', 12 * 60),
        ],
    ],

    'timesheets' => [
        'period' => [
            // 0 (Sunday) through 6 (Saturday). Defaults to Monday.
            'first_day_of_week' => (int) env('TIME_MANAGEMENT_TIMESHEET_FIRST_DAY_OF_WEEK', 1),
        ],
        'submission' => [
            'deadline_offset_days' => (int) env('TIME_MANAGEMENT_TIMESHEET_DEADLINE_OFFSET_DAYS', 1),
            'deadline_time' => (string) env('TIME_MANAGEMENT_TIMESHEET_DEADLINE_TIME', '17:00'),
        ],
        'validation' => [
            'min_daily_minutes' => (int) env('TIME_MANAGEMENT_TIMESHEET_MIN_DAILY_MINUTES', 0),
            'max_daily_minutes' => (int) env('TIME_MANAGEMENT_TIMESHEET_MAX_DAILY_MINUTES', 24 * 60),
            'overtime_weekly_minutes' => (int) env('TIME_MANAGEMENT_TIMESHEET_OVERTIME_WEEKLY_MINUTES', 40 * 60),
        ],
    ],

    'billing' => [
        // Used only if no employee/project/category rate is available.
        'default_rate' => env('TIME_MANAGEMENT_DEFAULT_BILLING_RATE'),
    ],

    'absences' => [
        // Holidays are configured globally today via `laravel-crm.business_hours.holidays`.
        // This setting is reserved for future team-specific overrides.
        'holidays' => [],
    ],
];
