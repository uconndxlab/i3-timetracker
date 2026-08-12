<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Services\EntraService;

class EntraAuthenticate
{
    protected $entraService;

    public function __construct(EntraService $entraService)
    {
        $this->entraService = $entraService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            // User is not authenticated, redirect to OIDC login
            return $this->entraService->generateRedirect();
        }
        
        return $next($request);
    }
}