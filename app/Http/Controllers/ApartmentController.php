<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\GeographicArea;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    use ZoneScope;

    /**
     * Liste paginée des appartements, filtrée par zone de l'utilisateur.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Apartment::select(
                'apartments.*',
                'users.nom as owner_nom',
                'users.telephone as owner_telephone'
            )
            ->join('users', 'apartments.owner_id', '=', 'users.id')
            ->with(['geographicArea.level'])
            ->withCount('households');

        $this->applyApartmentZoneFilter($query, $user);

        if ($request->geographic_area_id) {
            $area = GeographicArea::find($request->geographic_area_id);
            if ($area) {
                $descendantIds = $area->getDescendantIds();
                $query->whereIn('apartments.geographic_area_id', $descendantIds);
            }
        }
        if ($request->avenue) {
            $query->where('apartments.avenue', 'LIKE', "%{$request->avenue}%");
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('apartments.avenue', 'LIKE', "%{$search}%")
                  ->orWhere('apartments.numero', 'LIKE', "%{$search}%")
                  ->orWhere('apartments.description', 'LIKE', "%{$search}%")
                  ->orWhere('users.nom', 'LIKE', "%{$search}%")
                  ->orWhere('users.telephone', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $paginated = $query->orderBy('apartments.avenue')
            ->orderBy('apartments.numero')
            ->paginate($perPage);

        $avenuesQuery = Apartment::distinct();
        $this->applyApartmentZoneFilter($avenuesQuery, $user);
        if ($request->geographic_area_id) {
            $area = GeographicArea::find($request->geographic_area_id);
            if ($area) {
                $avenuesQuery->whereIn('geographic_area_id', $area->getDescendantIds());
            }
        }
        $avenues = $avenuesQuery->pluck('avenue')->sort()->values();

        return response()->json([
            'apartments' => $paginated->items(),
            'avenues' => $avenues,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * Détail d'un appartement avec ses ménages.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $apartment = Apartment::with([
            'owner:id,nom,telephone,photo_profil',
            'geographicArea.level',
            'households.chef:id,nom,telephone',
            'households.members',
        ])->findOrFail($id);

        $areaIds = $this->getZoneIds($user);
        if ($areaIds !== null && !in_array($apartment->geographic_area_id, $areaIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Cet appartement n\'est pas dans votre zone.',
            ], 403);
        }

        $ancestors = $apartment->geographicArea
            ? $apartment->geographicArea->ancestors()->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'level' => $a->level->name ?? '',
            ])
            : collect();

        return response()->json([
            'apartment' => $apartment,
            'full_address' => $apartment->full_address,
            'ancestors' => $ancestors->values(),
        ]);
    }

    /**
     * Créer un appartement (citoyen propriétaire).
     */
    public function store(Request $request)
    {
        $request->validate([
            'geographic_area_id' => 'required|exists:geographic_areas,id',
            'avenue' => 'required|string|max:255',
            'numero' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $apartment = Apartment::create([
            'owner_id' => $user->id,
            'geographic_area_id' => $request->geographic_area_id,
            'avenue' => trim($request->avenue),
            'numero' => trim($request->numero),
            'description' => $request->description ? trim($request->description) : null,
        ]);

        $this->notifyAuthorities(
            $apartment,
            'nouvel_appartement',
            'Nouvel appartement',
            "Un nouvel appartement a été enregistré: Avenue {$apartment->avenue}, N°{$apartment->numero}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Appartement enregistré avec succès',
            'apartment' => $apartment->load(['owner:id,nom,telephone', 'geographicArea.level']),
        ], 201);
    }

    /**
     * Modifier un appartement (propriétaire uniquement).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'avenue' => 'sometimes|required|string|max:255',
            'numero' => 'sometimes|required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== $user->id && !$user->isAuthority()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres appartements.',
            ], 403);
        }

        $apartment->update($request->only(['avenue', 'numero', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Appartement modifié avec succès',
            'apartment' => $apartment->fresh(['owner:id,nom,telephone', 'geographicArea.level']),
        ]);
    }

    /**
     * Supprimer un appartement (propriétaire ou autorité).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== $user->id && !$user->isAuthority()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres appartements.',
            ], 403);
        }

        if ($apartment->households()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet appartement car des ménages y sont rattachés.',
            ], 422);
        }

        $apartment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appartement supprimé avec succès',
        ]);
    }

    /**
     * Mes appartements (pour le citoyen connecté).
     */
    public function mine(Request $request)
    {
        $user = $request->user();

        $apartments = Apartment::where('owner_id', $user->id)
            ->with(['geographicArea.level'])
            ->withCount('households')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['apartments' => $apartments]);
    }

    /**
     * Lister les appartements sur une avenue dans un quartier donné.
     * Utilisé lors de l'enregistrement des ménages.
     */
    public function byAvenue(Request $request)
    {
        $request->validate([
            'geographic_area_id' => 'required|exists:geographic_areas,id',
            'avenue' => 'required|string',
        ]);

        $apartments = Apartment::where('geographic_area_id', $request->geographic_area_id)
            ->where('avenue', $request->avenue)
            ->with(['owner:id,nom,telephone'])
            ->withCount('households')
            ->orderBy('numero')
            ->get();

        return response()->json(['apartments' => $apartments]);
    }

    /**
     * Lister les avenues d'un quartier donné (pour les selects en cascade).
     */
    public function avenues(Request $request)
    {
        $request->validate([
            'geographic_area_id' => 'required|exists:geographic_areas,id',
        ]);

        $avenues = Apartment::where('geographic_area_id', $request->geographic_area_id)
            ->distinct()
            ->orderBy('avenue')
            ->pluck('avenue');

        return response()->json(['avenues' => $avenues]);
    }

    private function applyApartmentZoneFilter($query, User $user)
    {
        $areaIds = $this->getZoneIds($user);

        if ($areaIds === null) {
            return $query;
        }

        return $query->whereIn('apartments.geographic_area_id', $areaIds);
    }

    private function notifyAuthorities(Apartment $apartment, string $type, string $titre, string $message): void
    {
        $query = User::whereIn('role', User::AUTHORITY_ROLES);

        if ($apartment->geographic_area_id) {
            $area = GeographicArea::find($apartment->geographic_area_id);
            $ancestorIds = $area ? $area->ancestors()->pluck('id')->toArray() : [];
            $relevantIds = array_merge([$apartment->geographic_area_id], $ancestorIds);

            $query->where(function ($q) use ($relevantIds) {
                $q->whereIn('geographic_area_id', $relevantIds)
                  ->orWhereNull('geographic_area_id');
            });
        }

        foreach ($query->pluck('id') as $authorityId) {
            Notification::create([
                'user_id' => $authorityId,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
            ]);
        }
    }
}
