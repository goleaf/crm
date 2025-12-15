<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | The session key used to store the original user id.
    |
    */

    'session_key' => 'impersonated_by',

    /*
    |--------------------------------------------------------------------------
    | Take Redirect To
    |--------------------------------------------------------------------------
    |
    | Where to redirect after taking an impersonation.
    | Only used in the built-in controller.
    | You can use: an URI, the keyword 'back' (to redirect back) or a route name
    |
    */

    'take_redirect_to' => '/',

    /*
    |--------------------------------------------------------------------------
    | Leave Redirect To
    |--------------------------------------------------------------------------
    |
    | Where to redirect after leaving an impersonation.
    | Only used in the built-in controller.
    | You can use: an URI, the keyword 'back' (to redirect back) or a route name
    |
    */

    'leave_redirect_to' => '/',

];

