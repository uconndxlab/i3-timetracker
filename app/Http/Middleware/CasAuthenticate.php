<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CasAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! cas()->isAuthenticated()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            cas()->authenticate();
        }

        $netid = cas()->user();

        if (! User::where('netid', $netid)->exists()) {
            if ($request->routeIs('users.create', 'users.store')) {
                return $next($request);
            }

            return redirect()->route('users.create');
        }

        if (! Auth::check() || Auth::user()->netid !== $netid) {
            Auth::login(User::where('netid', $netid)->first());
        }

        return $next($request);
    }
}
