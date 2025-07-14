<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class Authenticate
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
        // if netid is not correlate to a user, route to create
        if (!User::where('netid', $netid)->exists()) {
            return redirect()->route('users.create');
        }

        $user = User::where('netid', $netid)->first();
        Auth::login($user);
        return $next($request);
    }
}
// {
//     public function handle(Request $request, Closure $next): Response
//     {
//         try {
//             $cas = app(CasClient::class);
//             $cas->forceAuthentication();

//             $netid = $cas->getUser();
//             $attributes = $cas->getAttributes();

//             $user = User::firstOrCreate(
//                 ['netid' => $netid],
//                 [
//                     'name' => $attributes['displayName'] ?? $netid,
//                     'email' => $attributes['mail'] ?? "{$netid}@uconn.edu",
//                 ]
//             );

//             Auth::login($user);

//             return $next($request);

//         } catch (Exception $e) {
//             Log::error('CAS Authentication failed: ' . $e->getMessage());
//             abort(500, 'CAS Authentication failed. Please contact support.');
//         }
//     }
// }