<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EntraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EntraController extends Controller
{
    public function __construct(
        protected EntraService $entraService
    ) {
    }

    public function redirect()
    {
        return $this->entraService->generateRedirect();
    }

    public function callback(Request $request)
    {
        $decoded = $this->entraService->handleCallback($request);

        $netid = $decoded->NetID ?? $decoded->netid ?? null;

        if (! $netid) {
            throw new RuntimeException(
                'The Entra response did not contain a NetID.'
            );
        }

        $user = User::where('netid', $netid)
            ->where('active', true)
            ->first();

        if (! $user) {
            abort(403, 'You do not have an account for this application.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
