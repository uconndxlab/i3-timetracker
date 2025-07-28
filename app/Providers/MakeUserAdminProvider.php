<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\MakeUserAdmin;

class MakeUserAdminProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->singleton('command.user.make-admin', function ($app) {
                return new MakeUserAdmin();
            });

            $this->commands([
                'command.user.make-admin',
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
