<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseholdController extends Controller
{
    public function index(Request $request)
    {
        $query = Household::select(
                'households.id',
                'households.chef_id',
                'households.quartier',
                'households.adresse',
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
                'households.created_at',
                'users.nom',
                'users.telephone'
            );

        if ($request->quartier) {
            $query->where('households.quartier', $request->quartier);
        }
        if ($request->zone) {
            $query->where('households.quartier', 'LIKE', "%{$request->zone}%");
        }
        if ($request->commune) {
            $query->where('households.quartier', 'LIKE', "%{$request->commune}%");
        }

        $households = $query->orderBy('households.quartier')
            ->orderByDesc('households.created_at')
            ->get();

        $quartiers = Household::distinct()->pluck('quartier')->sort()->values();

        return response()->json([
            'households' => $households,
            'quartiers' => $quartiers,
        ]);
    }

    public function show($id)
    {
        $household = Household::with('chef:id,nom,telephone,photo_profil')
            ->findOrFail($id);

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

    public function stats()
    {
        $stats = Household::select('households.quartier')
            ->selectRaw('COUNT(DISTINCT households.id) as nb_menages')
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'permanent' THEN members.id END) as nb_membres")
            ->selectRaw("COUNT(DISTINCT CASE WHEN members.type = 'invite' AND members.statut = 'present' THEN members.id END) as nb_invites_presents")
            ->leftJoin('members', 'households.id', '=', 'members.household_id')
            ->groupBy('households.quartier')
            ->orderBy('households.quartier')
            ->get();

        return response()->json($stats);
    }
}
