<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    /**
     * Admin: liste tous les utilisateurs qu'il a créés + tous les users
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Admin: créer un utilisateur (police, chef_quartier, ministere)
     */
    public function storeByAdmin(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|unique:users',
            'role' => 'required|in:chef_quartier,ministere,police,admin',
            'photo_profil' => 'nullable|image|max:5120',
        ]);

        $password = Str::random(8);

        $photoPath = null;
        if ($request->hasFile('photo_profil')) {
            $photoPath = $request->file('photo_profil')->store('profils', 'public');
            $photoPath = '/storage/' . $photoPath;
        }

        $user = User::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => $password,
            'role' => $request->role,
            'photo_profil' => $photoPath,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'password' => $password,
        ], 201);
    }

    /**
     * Chef de quartier: inscrire un citoyen
     */
    public function storeCitoyen(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|unique:users',
            'quartier' => 'required|string',
            'adresse' => 'required|string',
            'photo_profil' => 'nullable|image|max:5120',
        ]);

        $password = Str::random(8);

        $photoPath = null;
        if ($request->hasFile('photo_profil')) {
            $photoPath = $request->file('photo_profil')->store('profils', 'public');
            $photoPath = '/storage/' . $photoPath;
        }

        $user = User::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => $password,
            'role' => 'citoyen',
            'photo_profil' => $photoPath,
            'created_by' => $request->user()->id,
        ]);

        Household::create([
            'chef_id' => $user->id,
            'quartier' => $request->quartier,
            'adresse' => $request->adresse,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Citoyen inscrit avec succès',
            'user' => $user,
            'password' => $password,
        ], 201);
    }

    /**
     * Admin/Chef: voir un utilisateur
     */
    public function show($id)
    {
        $user = User::with('household')->findOrFail($id);
        return response()->json(['user' => $user]);
    }

    /**
     * Admin: modifier un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:citoyen,chef_quartier,ministere,admin,police',
        ]);

        $user->update($request->only(['nom', 'telephone', 'email', 'role']));

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur modifié',
            'user' => $user,
        ]);
    }

    /**
     * Admin: réinitialiser le mot de passe
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $password = Str::random(8);
        $user->update(['password' => $password]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé',
            'password' => $password,
        ]);
    }

    /**
     * Admin: supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un administrateur',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé',
        ]);
    }
}
