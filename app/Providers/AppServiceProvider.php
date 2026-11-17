<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\SocialiteProviders\Manager\ServiceProvider::class);
        $this->app->register(OpenIDConnectServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('oidc_entra', EntraProvider::class);
        });
    }
}
