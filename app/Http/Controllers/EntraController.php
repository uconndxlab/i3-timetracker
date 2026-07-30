<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EntraService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ExampleEntraController extends Controller
{

    protected $entraService;

    public function __construct(EntraService $entraService) {
        $this->entraService = $entraService;
    }

    // Initialize the OIDC flow by redirecting to the authorization endpoint
    public function redirect(Request $request) {
        return $this->entraService->generateRedirect();
    }

    // Handle a callback from OIDC provider.
    public function callback(Request $request) {
        $decoded = $this->entraService->handleCallback($request);
        // $decoded holds email, name, and other claims from the ID token.


        /**
         * BELOW THIS IS A BASIC EXAMPLE OF USING THE AUTH VALUES
         * You should modify how you handle user creation and login based on your application's needs.
         */
        $user = User::updateOrCreate(
            [ 'netid' => $decoded->NetID ], // NetID is specific to UConn - keying by 'oid' would be more appropriate for a general OIDC implementation
            [
                'email' => $decoded->email,
                'name' => $decoded->name
            ]
        );

        Auth::login($user);

        return redirect()->intended('/');
    }
}
