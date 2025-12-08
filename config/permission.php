<?php

declare(strict_types=1);

use App\Models\Role;
use App\Permissions\TeamResolver;

return [

    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null,
        'permission_pivot_key' => null,
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'register_permission_check_method' => true,

    'register_octane_reset_listener' => false,

    'events_enabled' => false,

    'teams' => true,

    'team_resolver' => TeamResolver::class,

    'use_passport_client_credentials' => false,

    'display_permission_in_exception' => false,

    'display_role_in_exception' => false,

    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],

    /**
     * Default permissions, roles, and role inheritance configuration.
     */
    'defaults' => [
        'guard' => 'web',
        'super_admin_roles' => ['admin'],
        'resources' => [
            'accounts' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'companies' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'contacts' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'leads' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'opportunities' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'notes' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'support-cases' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'tasks' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'invoices' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'knowledge-articles' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'knowledge-faqs' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'knowledge-tags' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'knowledge-template-responses' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete'],
            'teams' => ['view', 'manage-members', 'manage-roles', 'manage-permissions'],
            'settings' => ['manage'],
        ],
        'custom_permissions' => [
            'extensions.execute',
        ],
        'permission_sets' => [
            'billing' => [
                'invoices.view',
                'invoices.create',
                'invoices.update',
                'invoices.delete',
            ],
            'knowledge-management' => [
                'knowledge-articles.*',
                'knowledge-faqs.*',
                'knowledge-tags.*',
                'knowledge-template-responses.*',
            ],
            'sales' => [
                'leads.*',
                'opportunities.*',
                'accounts.view',
                'companies.view',
                'contacts.view',
                'notes.create',
                'notes.view',
            ],
            'support' => [
                'support-cases.*',
                'tasks.view',
                'tasks.update',
                'notes.view',
                'notes.create',
            ],
        ],
        'roles' => [
            'admin' => [
                'label' => 'Administrator',
                'description' => 'Full access to tenant data, billing, and settings.',
                'permissions' => ['*'],
            ],
            'editor' => [
                'label' => 'Editor',
                'description' => 'Create and update CRM records.',
                'permissions' => [
                    'accounts.*',
                    'companies.*',
                    'contacts.*',
                    'leads.*',
                    'opportunities.*',
                    'support-cases.*',
                    'tasks.*',
                    'notes.*',
                ],
                'permission_sets' => ['knowledge-management'],
                'inherits' => ['user'],
            ],
            'user' => [
                'label' => 'User',
                'description' => 'Read-only access to core CRM data.',
                'permissions' => [
                    'accounts.view',
                    'companies.view',
                    'contacts.view',
                    'leads.view',
                    'opportunities.view',
                    'support-cases.view',
                    'tasks.view',
                    'notes.view',
                    'knowledge-articles.view',
                    'knowledge-faqs.view',
                    'knowledge-tags.view',
                    'knowledge-template-responses.view',
                ],
            ],
            'billing' => [
                'label' => 'Billing',
                'description' => 'Manage invoices and payment records.',
                'permission_sets' => ['billing'],
                'inherits' => ['user'],
            ],
            'support' => [
                'label' => 'Support',
                'description' => 'Own the support desk with limited sales access.',
                'permission_sets' => ['support'],
                'inherits' => ['user'],
            ],
        ],
        'role_hierarchy' => [
            'admin' => ['editor', 'billing', 'support', 'user'],
            'editor' => ['support', 'user'],
        ],
        'team_role_map' => [
            'owner' => 'admin',
            'admin' => 'admin',
            'editor' => 'editor',
            'member' => 'user',
        ],
    ],
];
