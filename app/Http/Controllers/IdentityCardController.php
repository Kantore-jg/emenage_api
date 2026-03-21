<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentityCardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('household');

        if (!$user->photo_profil) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez avoir une photo de profil pour obtenir votre carte d\'identité biométrique.',
            ], 400);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'telephone' => $user->telephone,
                'role' => $user->role,
                'photo_profil' => $user->photo_profil,
                'quartier' => $user->household->quartier ?? null,
                'adresse' => $user->household->adresse ?? null,
            ],
            'dateEmission' => now()->format('d/m/Y'),
        ]);
    }
}
