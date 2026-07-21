<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Development Preview Banner
    |--------------------------------------------------------------------------
    |
    | When enabled, a banner and modal are shown to inform users that they are
    | viewing a development/staging instance of the Time Tracker and should
    | use the production site for actual time tracking.
    |
    */

    'enabled' => env('PREVIEW_BANNER_ENABLED', false),

    'production_url' => env('PREVIEW_PRODUCTION_URL', ''),

];
