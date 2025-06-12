<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use phpCAS;

class CasAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        app('cas');

        if (!phpCAS::isAuthenticated()) {
            phpCAS::forceAuthentication();
        }

        $netId = phpCAS::getUser();

        if (empty($netId)) {
            // should not happen
            abort(500, 'CAS authentication succeeded but no user ID was returned.');
        }

        if (Auth::check()) {
            if (Auth::user()->netid !== $netId) {
                Auth::logout();
            } else {
                return $next($request);
            }
        }

        $user = User::firstOrCreate(
            ['netid' => $netId],
            [
                'name' => $netId, 
                'email' => $netId . '@uconn.edu', 
                'password' => null, 
            ]
        );
        Auth::login($user);

        return $next($request);
    }
}