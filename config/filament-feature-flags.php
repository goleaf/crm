<?php

declare(strict_types=1);

use App\Filament\Resources\FeatureFlagResource;
use App\Models\Team;

return [
    // This package supports only class based features.

    /*
    * This is the default state for all class based features and
     * state will be used if there is no segmentation.
    */
    'default' => false,

    /*
     * Default scope: User::class, Team::class
     */
    'scope' => Team::class,

    /*
     * Column names and data source that can be used to activate or deactivate for a segment of users.
     * These columns must exist on the scope model and the data source must be a model.
     * COLUMN: The column name as defined on the default scope model config.
     * MODEL: The eloquent model of the source table.
     * VALUE: The column to be used as value.
     * KEY: The column to be used as key.
     */
    'segments' => [
        [
            'column' => 'id',
            'source' => [
                'model' => Team::class,
                'value' => 'name',
                'key' => 'id',
            ],
        ],
    ],

    'panel' => [
        /*
         * Navigation group translation key for the admin panel resource.
         */
        'group' => 'app.navigation.system_settings',

        /*
         * Navigation item translation key for the admin panel resource.
         */
        'label' => 'app.navigation.feature_flags',

        /*
         * Resource title translation key for the admin panel resource.
         */
        'title' => 'app.labels.feature_flag_segments',

        /*
         * Navigation item icon for admin panel resource.
         */
        'icon' => 'heroicon-o-cursor-arrow-ripple',
    ],

    'resources' => [
        FeatureFlagResource::class,
    ],
];
