<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant no válido. Contacte al administrador.',
            ], 403);
        }

        return $next($request);
    }
}
