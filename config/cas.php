<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Apereo CAS Client Configuration
    |--------------------------------------------------------------------------
    |
    | See https://apereo.github.io/phpCAS/api/latest/
    | for a list of valid options.
    |
    */
    'hostname'        => env('CAS_HOSTNAME', 'login.uconn.edu'),
    'port'            => (int) env('CAS_PORT', 443),
    'uri'             => env('CAS_URI', '/cas/login'), 

    /*
    |--------------------------------------------------------------------------
    | CAS Server CA Validation
    |--------------------------------------------------------------------------
    |
    | 'ca_cert_path': Path to the CA certificate bundle.
    | Set to null or an empty string to disable CA validation (NOT RECOMMENDED FOR PRODUCTION).
    |
    */
    'ca_cert_path'    => env('CAS_CA_CERT_PATH'), // certification seems to be available until 11/5/2025 (6 AM EST)

    /*
    |--------------------------------------------------------------------------
    | CAS Logout Redirect
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after CAS logout.
    |
    */
    'logout_redirect' => env('CAS_LOGOUT_REDIRECT', '/'),

    /*
    |--------------------------------------------------------------------------
    | CAS Debugging
    |--------------------------------------------------------------------------
    |
    | Enable debugging for phpCAS. This will write to a log file.
    | Set to false for production.
    |
    */
    'debug'           => env('CAS_DEBUG', true),
    'debug_log_path'  => storage_path('logs/phpcas.log'),
];