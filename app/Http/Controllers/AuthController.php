<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifiant' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifiant = $request->identifiant;

        $user = User::where('telephone', $identifiant)
            ->orWhere('email', $identifiant)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifiant' => ['Identifiant ou mot de passe incorrect'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->load(['household', 'geographicArea.level']);

        $zone = null;
        if ($user->geographicArea) {
            $zone = [
                'id' => $user->geographicArea->id,
                'name' => $user->geographicArea->name,
                'level' => $user->geographicArea->level->name,
                'full_path' => $user->geographicArea->full_path,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'zone' => $zone,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['household', 'geographicArea.level']);
        return response()->json($user);
    }
}
