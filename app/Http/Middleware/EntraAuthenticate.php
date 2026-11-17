<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntraAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
