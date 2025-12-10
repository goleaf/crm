<?php

/**
 * Login Link Configuration
 *
 * Configuration for the spatie/laravel-login-link package which provides
 * password-less authentication for development and testing environments.
 *
 * ## Security Model
 *
 * The Spatie package uses POST form submissions with CSRF protection,
 * NOT GET requests with signed URLs. Security is provided by:
 * - Environment restrictions (allowed_environments)
 * - Host restrictions (allowed_hosts)
 * - CSRF protection (web middleware)
 *
 * For signed URL authentication, use the custom dev.login route instead.
 *
 * @package    Relaticle\Config
 * @see        https://github.com/spatie/laravel-login-link
 * @see        docs/auth/developer-login.md
 *
 * @since      2025-12-08 Initial configuration
 * @updated    2025-12-08 Corrected middleware (removed 'signed' - not applicable to POST forms)
 */

declare(strict_types=1);

use Spatie\LoginLink\Http\Controllers\LoginLinkController;

return [
    /*
     * Login links will only work in these environments. In all
     * other environments, an exception will be thrown.
     */
    'allowed_environments' => ['local'],

    /*
     * Login links will only work in these hosts. In all
     * other hosts, an exception will be thrown.
     */
    'allowed_hosts' => array_values(array_unique(array_filter(array_merge(
        [
            'localhost',
            '127.0.0.1',
        ],
        // Add app URL host if configured
<<<<<<< HEAD
        ($appUrl = config('app.url')) && ($host = parse_url((string) $appUrl, PHP_URL_HOST)) ? [$host] : [],
=======
        ($appUrl = config('app.url')) && ($host = parse_url((string) $appUrl, PHP_URL_HOST)) ? [$host, 'app.'.$host] : [],
>>>>>>> d03887dc78a6e1a0c2ed674137398a067503335e
        // Add CRM domain if configured
        ($crmDomain = config('laravel-crm.routes.domain')) ? [$crmDomain, 'app.' . $crmDomain] : [],
        // Add custom hosts from env
        env('LOGIN_LINK_ALLOWED_HOSTS') ? array_map(trim(...), explode(',', (string) env('LOGIN_LINK_ALLOWED_HOSTS'))) : [],
    )))),

    /*
     * The package will automatically create a user model when trying
     * to log in a user that doesn't exist.
     */
    'automatically_create_missing_users' => true,

    /*
     * The user model that should be logged in. If this is set to `null`
     * we'll take a look at the model used for the `users`
     * provider in config/auth.php
     */
    'user_model' => null,

    /*
     * After a login link is clicked, we'll redirect the user to this route.
     * If it is set to `null`, we'll redirect the user to their last intended/requested url.
     * You can set it to `/`, for making redirect to the root page.
     */
    'redirect_route_name' => null,

    /*
     * The package will register a route that points to this controller. To have fine
     * grained control over what happens when a login link is clicked, you can
     * override this class.
     */
    'login_link_controller' => LoginLinkController::class,

    /*
     * This middleware will be applied on the route
     * that logs in a user via a link.
     *
     * NOTE: The 'signed' middleware is NOT used here because the Spatie package
     * uses POST form submissions, not GET requests with signed URLs.
     * Security is provided by:
     * - Environment restrictions (allowed_environments)
     * - Host restrictions (allowed_hosts)
     * - CSRF protection (web middleware)
     *
     * For signed URL authentication, use our custom dev.login route instead:
     * - URL::temporarySignedRoute('dev.login', now()->addMinutes(30), ['email' => '...'])
     *
     * @see docs/auth/developer-login.md
     */
    'middleware' => ['web'],
];
