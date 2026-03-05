<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('household');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'telephone' => $user->telephone,
                'email' => $user->email,
                'role' => $user->role,
                'photo_profil' => $user->photo_profil,
                'quartier' => $user->household->quartier ?? null,
                'adresse' => $user->household->adresse ?? null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users,telephone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6',
            'confirm_password' => 'nullable|string|same:new_password',
            'quartier' => 'nullable|string',
            'adresse' => 'nullable|string',
            'photo_profil' => 'nullable|image|max:5120',
        ]);

        if ($request->password && $request->new_password) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ancien mot de passe incorrect',
                ], 422);
            }
            $user->password = $request->new_password;
        }

        if ($request->hasFile('photo_profil')) {
            if ($user->photo_profil) {
                $oldPath = str_replace('/storage/', '', $user->photo_profil);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('photo_profil')->store('profils', 'public');
            $user->photo_profil = '/storage/' . $path;
        }

        $user->nom = trim($request->nom);
        $user->telephone = $request->telephone;
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        $user->save();

        if ($user->role === 'citoyen' && ($request->quartier || $request->adresse)) {
            $household = Household::where('chef_id', $user->id)->first();
            if ($household) {
                $household->update([
                    'quartier' => $request->quartier ?? $household->quartier,
                    'adresse' => $request->adresse ?? $household->adresse,
                ]);
            }
        }

        $user->load('household');

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => $user,
        ]);
    }
}
