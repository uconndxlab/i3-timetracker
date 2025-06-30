<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use phpCAS;

class CasServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    
    public function register(): void
    {
        $this->app->singleton('cas', function (Application $app) {
            phpCAS::client(SAML_VERSION_1_1, "login.uconn.edu", 443, "cas");
            Log::info('CAS service initialized.');
            return phpCAS::getInstance();
        });
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}