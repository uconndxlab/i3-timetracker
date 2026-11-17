<?php

namespace App\Services;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service for handling Microsoft Entra ID authentication via OpenID Connect (socialiteproviders/openidconnect).
 */
class EntraService
{
    /**
     * The Socialite driver name registered by socialiteproviders/openidconnect.
     */
    protected string $driver = 'oidc_entra';

    /**
     * Initializes the OIDC flow by redirecting to the Entra ID authorization endpoint.
     *
     * @return RedirectResponse
     */
    public function generateRedirect()
    {
        return Socialite::driver($this->driver)->stateless()->redirect();
    }

    /**
     * Handles the callback from Entra ID via the OpenID Connect Socialite driver.
     * Extracts claims, user details, and the UConn NetID.
     */
    public function handleCallback(?Request $request = null): array
    {
        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = Socialite::driver($this->driver)->stateless()->user();

        $rawUser = $socialiteUser->getRaw() ?? [];
        $netid = null;

        // 1. Check ID token payload if available in accessTokenResponseBody
        if (isset($socialiteUser->accessTokenResponseBody['id_token'])) {
            $idToken = $socialiteUser->accessTokenResponseBody['id_token'];
            $parts = explode('.', $idToken);
            if (isset($parts[1])) {
                $tokenClaims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?? [];
                $netid = $tokenClaims['NetID'] ?? $tokenClaims['netid'] ?? null;
            }
        }

        // 2. Check claims array directly
        if (! $netid && is_array($rawUser)) {
            $netid = $rawUser['NetID']
                ?? $rawUser['netid']
                ?? $rawUser['onPremisesSamAccountName']
                ?? null;
        }

        // 3. Fallback: Parse NetID from preferred_username, userPrincipalName, upn, or email
        if (! $netid) {
            $principal = $rawUser['preferred_username']
                ?? $rawUser['userPrincipalName']
                ?? $rawUser['upn']
                ?? $socialiteUser->getEmail();

            if ($principal && str_contains($principal, '@')) {
                $netid = explode('@', $principal)[0];
            } else {
                $netid = $principal;
            }
        }

        $email = $socialiteUser->getEmail() ?? ($rawUser['mail'] ?? ($rawUser['userPrincipalName'] ?? null));
        $name = $socialiteUser->getName() ?? ($rawUser['displayName'] ?? null);

        return [
            'netid' => $netid,
            'email' => $email,
            'name' => $name,
            'socialite_user' => $socialiteUser,
            'raw' => $rawUser,
        ];
    }
}
