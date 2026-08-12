<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * Service for handling Microsoft Entra OpenID Connect authentication.
 */
class EntraService
{

    /**
     * Initializes the OIDC flow by generating the authorization URL and returning the redirect response.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateRedirect()
    {
        // Requires a few config values, let's make sure they exist
        $clientId = config('services.entra.client_id');
        $tenantId = config('services.entra.tenant_id');
        $redirectUri = config('services.entra.redirect');
        $clientSecret = config('services.entra.client_secret');

        if (!$clientId || !$tenantId || !$redirectUri || !$clientSecret) {
            throw new \Exception('Entra configuration is missing. Please set client_id, tenant_id, client_secret, and redirect in config/services.php');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        // Store state and nonce in session for later validation
        session([
            'oidc_state' => $state,
            'oidc_nonce' => $nonce,
        ]);

        $queryParams = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return redirect()->away(
            'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/authorize?' . $queryParams
        );
    }

    /**
     * Handles the OIDC callback by validating the state, exchanging the authorization code for tokens,
     * and validating the ID token. Returns the decoded ID token on success.
     *
     * @param Request $request
     * @return object
     * @throws \Exception
     */
    public function handleCallback(Request $request)
    {
        if ($request->filled('error')) {
            throw new \Exception(
                'Entra authentication failed: ' .
                $request->input('error_description', $request->input('error'))
            );
        }

        // Validate state
        $expectedState = $request->session()->pull('oidc_state');

        if (
            !$request->filled('state') ||
            !$expectedState ||
            !hash_equals($expectedState, $request->input('state'))
        ) {
            throw new \Exception('Invalid state');
        }

        if (!$request->filled('code')) {
            throw new \Exception('Authorization code missing');
        }

        // Exchange the authorization code for tokens
        $tokenResponse = Http::asForm()->post(
            'https://login.microsoftonline.com/' . config('services.entra.tenant_id') . '/oauth2/v2.0/token',
            [
                'client_id' => config('services.entra.client_id'),
                'client_secret' => config('services.entra.client_secret'),
                'code' => $request->input('code'),
                'redirect_uri' => config('services.entra.redirect'),
                'grant_type' => 'authorization_code',
            ]
        );

        if ($tokenResponse->failed()) {
            throw new \Exception('Token exchange failed');
        }

        $tokens = $tokenResponse->json();

        if (empty($tokens['id_token'])) {
            throw new \Exception('ID token missing');
        }

        // Validate and decode the ID token
        try {
            $jwksResponse = Http::get(
                'https://login.microsoftonline.com/' .
                config('services.entra.tenant_id') .
                '/discovery/v2.0/keys'
            );

            if ($jwksResponse->failed()) {
                throw new \Exception('Unable to retrieve signing keys');
            }

            $decoded = JWT::decode(
                $tokens['id_token'],
                JWK::parseKeySet($jwksResponse->json(), 'RS256')
            );
        } catch (\Exception $e) {
            throw new \Exception('Invalid ID token: ' . $e->getMessage());
        }

        // Validate nonce
        $expectedNonce = $request->session()->pull('oidc_nonce');

        if (
            !isset($decoded->nonce) ||
            !$expectedNonce ||
            !hash_equals($expectedNonce, $decoded->nonce)
        ) {
            throw new \Exception('Invalid nonce');
        }

        return $decoded;
    }

}