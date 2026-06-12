<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;


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
            'cas.auth' => \App\Http\Middleware\CasAuthenticate::class,
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })

    ->withExceptions(function ($exceptions) {
        //
    })->create();