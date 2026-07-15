<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionPlan
{
    protected array $premiumRoutes = [
        'reportes.pdf',
        'reportes.excel',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->plan_suscripcion === 'gratuito') {
            $routeName = $request->route()?->getName();

            if (in_array($routeName, $this->premiumRoutes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta función requiere plan Premium. Actualice su suscripción.',
                    'requires_upgrade' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
