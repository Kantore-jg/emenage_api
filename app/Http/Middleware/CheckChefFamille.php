<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckChefFamille
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $household = $user->household;

        if (!$household) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être chef de famille pour accéder à cette fonctionnalité.',
            ], 403);
        }

        $request->merge(['household' => $household]);

        return $next($request);
    }
}
