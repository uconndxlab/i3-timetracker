<?php

use App\Http\Middleware\EntraAuthenticate;
use App\Http\Middleware\IsAdmin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('db:backup')->daily();
    })

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'entra' => \App\Http\Middleware\EntraAuthenticate::class,
            'entra' => EntraAuthenticate::class,
            'entra.auth' => EntraAuthenticate::class,
            'admin' => IsAdmin::class,
        ]);
    })

    ->withExceptions(function ($exceptions) {
        //
    })->create();
