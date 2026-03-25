<?php

namespace App\Http\Controllers;

use App\Models\GeographicArea;
use App\Models\GeographicLevel;
use App\Models\Household;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    use ZoneScope;

    /**
     * Liste les utilisateurs visibles par l'utilisateur connecté (dans sa zone).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::with('geographicArea:id,name,level_id');

        $userIds = $this->getAccessibleUserIds($user);
        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }

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

        $perPage = $request->input('per_page', 15);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'users' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
            'creatable_roles' => $user->getCreatableRoles(),
        ]);
    }

    /**
     * Inscription hiérarchique:
     * admin → ministere, ministere → provincial, provincial → communal,
     * communal → zonal, zonal → collinaire, collinaire → citoyen.
     *
     * Chaque niveau ne peut inscrire que le niveau directement en dessous
     * et uniquement dans sa propre zone géographique ou ses descendants.
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();
        $targetRole = $request->input('role');

        if (!$targetRole || !$currentUser->canRegister($targetRole)) {
            $allowed = implode(', ', $currentUser->getCreatableRoles());
            return response()->json([
                'success' => false,
                'message' => "Vous ne pouvez créer que des comptes: {$allowed}.",
            ], 403);
        }

        $rules = [
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|unique:users',
            'role' => 'required|in:' . implode(',', User::ALL_ROLES),
            'photo_profil' => 'nullable|image|max:5120',
        ];

        $needsGeo = !in_array($targetRole, ['admin']);
        $expectedGeoLevel = User::ROLE_GEO_LEVEL[$targetRole] ?? null;

        if ($needsGeo && $expectedGeoLevel) {
            $rules['geographic_area_id'] = 'required|exists:geographic_areas,id';
        } else {
            $rules['geographic_area_id'] = 'nullable|exists:geographic_areas,id';
        }

        if ($targetRole === 'citoyen') {
            $rules['adresse'] = 'required|string';
        }

        $request->validate($rules);

        // Vérifier que la zone sélectionnée correspond au bon niveau géographique
        if ($request->geographic_area_id && $expectedGeoLevel) {
            $area = GeographicArea::with('level')->findOrFail($request->geographic_area_id);
            if ($area->level->slug !== $expectedGeoLevel) {
                return response()->json([
                    'success' => false,
                    'message' => "Un {$targetRole} doit être assigné à une zone de type \"{$expectedGeoLevel}\". Vous avez sélectionné \"{$area->level->name}\".",
                ], 422);
            }
        }

        // Vérifier que la zone est dans le périmètre de l'inscripteur
        if ($request->geographic_area_id && !$currentUser->isAdmin()) {
            $accessibleIds = $currentUser->getAccessibleAreaIds();
            if ($accessibleIds !== null && !in_array((int)$request->geographic_area_id, $accessibleIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette zone n\'est pas dans votre périmètre géographique.',
                ], 403);
            }
        }

        $password = Str::random(8);

        $photoPath = null;
        if ($request->hasFile('photo_profil')) {
            $photoPath = $request->file('photo_profil')->store('profils', 'public');
            $photoPath = '/storage/' . $photoPath;
        }

        $geoAreaId = in_array($targetRole, ['admin']) ? null : $request->geographic_area_id;

        $user = User::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => $password,
            'role' => $targetRole,
            'photo_profil' => $photoPath,
            'created_by' => $currentUser->id,
            'geographic_area_id' => $geoAreaId,
        ]);

        // Si citoyen, créer aussi le ménage
        if ($targetRole === 'citoyen' && $request->adresse) {
            $area = GeographicArea::find($geoAreaId);
            Household::create([
                'chef_id' => $user->id,
                'quartier' => $area ? $area->name : '',
                'adresse' => $request->adresse,
                'geographic_area_id' => $geoAreaId,
            ]);
        }

        $user->load('geographicArea:id,name,level_id');
        $zone_info = $user->geographicArea ? $user->geographicArea->full_path : 'Tout le pays';

        return response()->json([
            'success' => true,
            'message' => ucfirst($targetRole) . ' inscrit avec succès',
            'user' => $user,
            'password' => $password,
            'zone' => $zone_info,
        ], 201);
    }

    /**
     * Voir un utilisateur (avec vérification de zone).
     */
    public function show(Request $request, $id)
    {
        $user = User::with(['household.geographicArea.level', 'geographicArea.level'])->findOrFail($id);

        return response()->json([
            'user' => $user,
            'zone' => $user->geographicArea ? $user->geographicArea->full_path : 'Tout le pays',
        ]);
    }

    /**
     * Modifier un utilisateur.
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:' . implode(',', User::ALL_ROLES),
            'geographic_area_id' => 'nullable|exists:geographic_areas,id',
        ]);

        // Seul un admin ou le supérieur hiérarchique peut modifier
        if (!$currentUser->isAdmin() && $user->created_by !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que les comptes que vous avez créés.',
            ], 403);
        }

        $data = $request->only(['nom', 'telephone', 'email']);

        if ($request->has('role') && $currentUser->isAdmin()) {
            $data['role'] = $request->role;
        }

        if ($request->has('geographic_area_id')) {
            $newRole = $data['role'] ?? $user->role;
            $data['geographic_area_id'] = $newRole === 'admin' ? null : $request->geographic_area_id;
        }

        $user->update($data);
        $user->load('geographicArea:id,name,level_id');

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur modifié',
            'user' => $user,
            'zone' => $user->geographicArea ? $user->geographicArea->full_path : 'Tout le pays',
        ]);
    }

    /**
     * Réinitialiser le mot de passe (admin ou supérieur hiérarchique).
     */
    public function resetPassword(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::findOrFail($id);

        if (!$currentUser->isAdmin() && $user->created_by !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez réinitialiser que les comptes que vous avez créés.',
            ], 403);
        }

        $password = Str::random(8);
        $user->update(['password' => $password]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé',
            'password' => $password,
        ]);
    }

    /**
     * Supprimer un utilisateur (admin ou supérieur hiérarchique).
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un administrateur',
            ], 403);
        }

        if (!$currentUser->isAdmin() && $user->created_by !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que les comptes que vous avez créés.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé',
        ]);
    }
}
