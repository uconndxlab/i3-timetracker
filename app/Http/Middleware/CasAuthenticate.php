<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class CasAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!cas()->isAuthenticated()) {
            cas()->authenticate();
        }

        $netid = cas()->user();
        if (!User::where('netid', $netid)->exists()) {
            return redirect()->route('users.create');
        }

        $user = User::where('netid', $netid)->first();
        if ( !$user ) {
            Auth::logout();
            return redirect('/')->with('error', 'You are not authorized to access this page.');
        }

        Auth::login($user);
        return $next($request);
    }
}