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
        $user->load('household');

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
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
        $user->load('household');
        return response()->json($user);
    }
}
