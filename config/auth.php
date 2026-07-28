<?php

use App\Models\Guardian;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | The parent API is guardian-only, so the JWT `guardian` guard is the
    | default here. The staff `web` guard is kept so the shared models (and
    | the `users` provider they morph to) resolve exactly as they do in the
    | admin console — nothing in this app authenticates against it.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'guardian'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'guardians'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Guardians authenticate against the `guardians` table via a stateless
    | JWT bearer token — never against the staff `users` table, and never
    | through a session.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'guardian' => [
            'driver' => 'jwt',
            'provider' => 'guardians',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'guardians' => [
            'driver' => 'eloquent',
            'model' => Guardian::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Guardians get their own broker AND their own token table. The default
    | table is keyed by email, and this database is shared with the admin
    | console — a teacher who is also a parent would otherwise have one row
    | for two identities, so each side's reset link would silently cancel
    | the other's (and either could redeem the other's token).
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'guardians' => [
            'provider' => 'guardians',
            'table' => 'guardian_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
