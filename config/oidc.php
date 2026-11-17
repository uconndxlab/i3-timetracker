<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver prefix
    |--------------------------------------------------------------------------
    |
    | Each connection below is registered as a Socialite driver named
    | "{prefix}{connection}". With the default prefix, a 'keycloak' connection
    | becomes Socialite::driver('oidc_keycloak').
    |
    */

    'driver_prefix' => 'oidc_',

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [
        'entra' => [
            'provider' => 'entra',
            'tenant' => env('ENTRA_TENANT_ID', env('ENTRA_TENANT', 'common')),
            'client_id' => env('ENTRA_CLIENT_ID'),
            'client_secret' => env('ENTRA_CLIENT_SECRET'),
            'redirect' => env('ENTRA_REDIRECT_URI'),
        ],
    ],

];
