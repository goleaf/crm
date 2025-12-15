<?php

declare(strict_types=1);

return [
    'preferences' => [
        'title' => 'Notification preferences',
        'description' => 'Choose how you want to receive alerts.',
        'in_app' => 'In-app notifications',
        'in_app_help' => 'Show alerts in the app notification center.',
        'email' => 'Email notifications',
        'email_help' => 'Send alerts to your email address.',
        'realtime' => 'Real-time updates',
        'realtime_help' => 'Push updates over websockets when online.',
        'activity_alerts' => 'Activity alerts',
        'activity_alerts_help' => 'Notify when records change status.',
        'save' => 'Save preferences',
        'saved' => 'Notification preferences saved.',
    ],

    'milestones' => [
        'assigned_title' => 'Milestone assigned',
        'assigned_body' => 'You were assigned to ":title" (target: :date).',
        'status_changed_title' => 'Milestone status changed',
        'status_changed_body' => '":title" changed from :from to :to.',
        'progress_threshold_title' => 'Milestone progress: :percent%',
        'progress_threshold_body' => '":title" reached :percent% completion.',
        'dependency_shift_title' => 'Milestone date adjusted',
        'dependency_shift_body' => '":title" was moved by :days day(s) due to a dependency delay.',
        'ready_for_review_title' => 'Milestone ready for review',
        'ready_for_review_body' => 'All deliverables for ":title" are complete. It is ready for review.',
        'approval_requested_title' => 'Milestone approval requested',
        'approval_requested_body' => 'Please review and approve milestone ":title".',
        'approval_rejected_title' => 'Milestone approval rejected',
        'approval_rejected_body' => 'Milestone ":title" was rejected. :comment',
        'completed_title' => 'Milestone completed',
        'completed_body' => 'Milestone ":title" has been completed.',
    ],
];
