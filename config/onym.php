<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filename
    |--------------------------------------------------------------------------
    |
    | This value will be used as the filename when no filename is provided
    |
    */
    'default_filename' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Default Extension
    |--------------------------------------------------------------------------
    |
    | This value will be used as the extension when no extension is provided
    |
    */
    'default_extension' => 'txt',

    /*
    |--------------------------------------------------------------------------
    | Default Strategy
    |--------------------------------------------------------------------------
    |
    | This value will be used as the strategy when no strategy is provided
    |
    */
    'strategy' => 'uuid',

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | This value will be used as the options when no options are provided
    |
    */
    'options' => [

        'random' => [
            'length' => 16,
            'prefix' => '',
            'suffix' => '',
        ],

        'uuid' => [
            'prefix' => '',
            'suffix' => '',
        ],

        'timestamp' => [
            'format' => 'Y-m-d_H-i-s',
            'prefix' => '',
            'suffix' => '',
        ],

        'date' => [
            'format' => 'Y-m-d',
            'prefix' => '',
            'suffix' => '',
        ],

        'numbered' => [
            'number' => 1,
            'separator' => '_',
            'prefix' => '',
            'suffix' => '',
        ],

        'hash' => [
            'algorithm' => 'md5',
            'length' => 16,
            'prefix' => '',
            'suffix' => '',
        ],

        'slug' => [
            'prefix' => '',
            'suffix' => '',
        ],
    ],
];
