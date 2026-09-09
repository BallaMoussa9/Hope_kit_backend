<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à une liste de rôles.
 *
 * Utilisation dans les routes :
 *   Route::get('/dashboard/kpi', ...)->middleware('role:direction,coordinator');
 *   Route::post('/mobile/kits/scan', ...)->middleware('role:agent');
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => "Accès refusé : votre rôle ne permet pas cette action.",
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé.',
            ], 403);
        }

        return $next($request);
    }
}
