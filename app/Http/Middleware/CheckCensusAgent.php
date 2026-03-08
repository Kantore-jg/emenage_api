<?php

namespace App\Http\Middleware;

use App\Models\CensusAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCensusAgent
{
    /**
     * Vérifie que l'utilisateur est un agent assigné à au moins un recensement actif,
     * ou qu'il est une autorité (admin, ministere, etc.)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        if ($user->isAuthority()) {
            return $next($request);
        }

        if ($user->role === 'agent_recensement') {
            $hasAssignment = CensusAgent::where('user_id', $user->id)->exists();
            if ($hasAssignment) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Accès refusé. Vous n\'êtes pas agent de recensement.',
        ], 403);
    }
}
