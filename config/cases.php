<?php

declare(strict_types=1);

use App\Enums\CasePriority;
use App\Enums\CaseType;

return [
    /*
    |--------------------------------------------------------------------------
    | SLA Configuration
    |--------------------------------------------------------------------------
    |
    | Define SLA response and resolution times (in minutes) based on priority.
    | These values determine when cases breach their SLA targets.
    |
    */
    'sla' => [
        'response_time' => [
            CasePriority::P1->value => 15,  // 15 minutes for critical
            CasePriority::P2->value => 60,  // 1 hour for high
            CasePriority::P3->value => 240, // 4 hours for normal
            CasePriority::P4->value => 480, // 8 hours for low
        ],
        'resolution_time' => [
            CasePriority::P1->value => 240,  // 4 hours for critical
            CasePriority::P2->value => 480,  // 8 hours for high
            CasePriority::P3->value => 1440, // 24 hours for normal
            CasePriority::P4->value => 2880, // 48 hours for low
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation Configuration
    |--------------------------------------------------------------------------
    |
    | Define escalation rules based on priority and breach duration.
    | Escalation levels increase as cases remain unresolved past SLA.
    |
    */
    'escalation' => [
        'enabled' => true,
        'levels' => [
            1 => [
                'threshold_minutes' => 30, // Escalate 30 minutes after breach
                'notify_roles' => ['support-manager'],
            ],
            2 => [
                'threshold_minutes' => 120, // Escalate 2 hours after breach
                'notify_roles' => ['support-manager', 'director'],
            ],
            3 => [
                'threshold_minutes' => 240, // Escalate 4 hours after breach
                'notify_roles' => ['support-manager', 'director', 'executive'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Routing Rules
    |--------------------------------------------------------------------------
    |
    | Define automatic queue assignment based on case attributes.
    | Rules are evaluated in order; first match wins.
    |
    */
    'queue_routing' => [
        'enabled' => true,
        'rules' => [
            [
                'name' => 'Critical Priority',
                'conditions' => [
                    'priority' => [CasePriority::P1->value],
                ],
                'queue' => 'critical',
                'team_id' => null, // Can be set to specific team ID
            ],
            [
                'name' => 'Technical Issues',
                'conditions' => [
                    'type' => [CaseType::INCIDENT->value, CaseType::PROBLEM->value],
                ],
                'queue' => 'technical',
                'team_id' => null,
            ],
            [
                'name' => 'Service Requests',
                'conditions' => [
                    'type' => [CaseType::REQUEST->value],
                ],
                'queue' => 'service',
                'team_id' => null,
            ],
            [
                'name' => 'General Questions',
                'conditions' => [
                    'type' => [CaseType::QUESTION->value],
                ],
                'queue' => 'general',
                'team_id' => null,
            ],
        ],
        'default_queue' => 'general',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email-to-Case Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email parsing and case creation from incoming emails.
    |
    */
    'email_to_case' => [
        'enabled' => true,
        'default_priority' => CasePriority::P3->value,
        'default_type' => CaseType::QUESTION->value,
        'thread_tracking' => true,
        'auto_assign' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal Configuration
    |--------------------------------------------------------------------------
    |
    | Configure customer portal case submission and visibility.
    |
    */
    'portal' => [
        'enabled' => true,
        'default_priority' => CasePriority::P3->value,
        'auto_visible' => true, // Make portal-submitted cases visible to customer
        'allow_attachments' => true,
    ],
];
