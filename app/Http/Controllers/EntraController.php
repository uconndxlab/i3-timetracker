<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EntraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntraController extends Controller
{
    public function __construct(
        protected EntraService $entraService
    ) {}

    /**
     * Initialize the OIDC flow by redirecting to Microsoft Entra ID.
     */
    public function redirect()
    {
        return $this->entraService->generateRedirect();
    }

    /**
     * Handle the OIDC callback from Entra ID.
     */
    public function callback(Request $request)
    {
        try {
            $entraData = $this->entraService->handleCallback($request);
        } catch (\Throwable $e) {
            logger()->error('Entra callback error: '.$e->getMessage(), ['exception' => $e]);

            return redirect('/login')->with('error', 'Authentication failed: '.$e->getMessage());
        }

        $netid = $entraData['netid'] ?? null;

        if (! $netid) {
            abort(403, 'The Entra response did not contain a NetID.');
        }

        $user = User::where('netid', $netid)
            ->where('active', true)
            ->first();

        if (! $user) {
            abort(403, 'You do not have an account for this application.');
        }

        // Update name or email from Entra claims if present
        if (! empty($entraData['name']) && empty($user->name)) {
            $user->name = $entraData['name'];
        }
        if (! empty($entraData['email']) && empty($user->email)) {
            $user->email = $entraData['email'];
        }
        if ($user->isDirty()) {
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $tenantId = config('services.entra.tenant_id') ?? 'common';
        $logoutUrl = 'https://login.microsoftonline.com/'
            .$tenantId
            .'/oauth2/v2.0/logout?'
            .http_build_query([
                'post_logout_redirect_uri' => url('/'),
            ]);

        return redirect()->away($logoutUrl);
    }
}
