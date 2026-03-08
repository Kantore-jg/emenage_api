<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Payment;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseholdController extends Controller
{
    use ZoneScope;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Household::select(
                'households.id',
                'households.chef_id',
                'households.quartier',
                'households.adresse',
                'households.geographic_area_id',
                'households.created_at',
                'users.nom as chef_nom',
                'users.telephone as chef_telephone'
            )
            ->join('users', 'households.chef_id', '=', 'users.id')
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'permanent' THEN members.id END) as nb_membres")
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'invite' AND members.statut = 'present' THEN members.id END) as nb_invites_presents")
            ->leftJoin('members', 'households.id', '=', 'members.household_id')
            ->groupBy(
                'households.id',
                'households.chef_id',
                'households.quartier',
                'households.adresse',
                'households.geographic_area_id',
                'households.created_at',
                'users.nom',
                'users.telephone'
            );

        $this->applyHouseholdZoneFilter($query, $user);

        if ($request->geographic_area_id) {
            $query->where('households.geographic_area_id', $request->geographic_area_id);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('households.quartier', 'LIKE', "%{$search}%")
                  ->orWhere('users.nom', 'LIKE', "%{$search}%");
            });
        }

        $households = $query->orderBy('households.quartier')
            ->orderByDesc('households.created_at')
            ->get();

        // Quartiers disponibles dans la zone de l'utilisateur
        $quartiersQuery = Household::distinct();
        $this->applyHouseholdZoneFilter($quartiersQuery, $user);
        $quartiers = $quartiersQuery->pluck('quartier')->sort()->values();

        return response()->json([
            'households' => $households,
            'quartiers' => $quartiers,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $household = Household::with([
            'chef:id,nom,telephone,photo_profil',
            'geographicArea.level',
        ])->findOrFail($id);

        // Vérifier l'accès à la zone
        $areaIds = $this->getZoneIds($user);
        if ($areaIds !== null && !in_array($household->geographic_area_id, $areaIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Ce ménage n\'est pas dans votre zone.',
            ], 403);
        }

        $members = $household->permanentMembers()->orderByDesc('created_at')->get();
        $invites = $household->invites()->orderByDesc('created_at')->get();
        $payments = Payment::where('household_id', $id)->orderByDesc('date_paiement')->get();

        $stats = [
            'total_membres' => $members->count(),
            'membres_valides' => $members->where('statut_validation', 'valide')->count(),
            'membres_en_attente' => $members->where('statut_validation', 'en_attente')->count(),
            'invites_presents' => $invites->where('statut', 'present')->count(),
            'invites_total' => $invites->count(),
            'paiements_valides' => $payments->where('statut_validation', 'valide')->count(),
        ];

        return response()->json([
            'household' => $household,
            'members' => $members,
            'invites' => $invites,
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $query = Household::select('households.quartier')
            ->selectRaw('COUNT(DISTINCT households.id) as nb_menages')
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'permanent' THEN members.id END) as nb_membres")
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'invite' AND members.statut = 'present' THEN members.id END) as nb_invites_presents")
            ->leftJoin('members', 'households.id', '=', 'members.household_id')
            ->groupBy('households.quartier')
            ->orderBy('households.quartier');

        $this->applyHouseholdZoneFilter($query, $user);

        return response()->json($query->get());
    }
}
