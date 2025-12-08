<?php

declare(strict_types=1);

use App\Models\User;

return [
    'table' => 'reactions',

    'user' => [
        'model' => User::class,
        'foreign_key' => 'user_id',
        'table' => 'users',
        'guard' => 'web',
        'foreign_key_type' => 'id',
    ],
];
