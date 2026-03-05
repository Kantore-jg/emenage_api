<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Report;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function citoyen(Request $request)
    {
        $user = $request->user();
        $household = $user->household;

        if (!$household) {
            return response()->json([
                'household' => null,
                'members' => [],
                'invites' => [],
                'notifications' => [],
            ]);
        }

        $members = $household->permanentMembers()->orderByDesc('created_at')->get();
        $invites = $household->presentInvites()->orderByDesc('created_at')->get();
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'household' => $household,
            'members' => $members,
            'invites' => $invites,
            'notifications' => $notifications,
        ]);
    }

    public function gouvernement(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $nouveauxMembres = Member::with(['household.chef'])
            ->where('statut_validation', 'en_attente')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'nom' => $m->nom,
                    'age' => $m->age,
                    'type' => $m->type,
                    'telephone' => $m->telephone,
                    'photo_cni' => $m->photo_cni,
                    'statut_validation' => $m->statut_validation,
                    'created_at' => $m->created_at,
                    'quartier' => $m->household->quartier ?? '',
                    'adresse' => $m->household->adresse ?? '',
                    'chef_nom' => $m->household->chef->nom ?? '',
                ];
            });

        $nouveauxPaiements = Payment::with(['household.chef'])
            ->where('statut_validation', 'en_attente')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'motif' => $p->motif,
                    'motif_autre' => $p->motif_autre,
                    'montant' => $p->montant,
                    'date_paiement' => $p->date_paiement,
                    'recu_photo' => $p->recu_photo,
                    'statut_validation' => $p->statut_validation,
                    'created_at' => $p->created_at,
                    'quartier' => $p->household->quartier ?? '',
                    'chef_nom' => $p->household->chef->nom ?? '',
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'nouveauxMembres' => $nouveauxMembres,
            'nouveauxPaiements' => $nouveauxPaiements,
        ]);
    }

    public function securite()
    {
        $reports = Report::with('citizen:id,nom,telephone')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'description' => $r->description,
                    'latitude' => $r->latitude,
                    'longitude' => $r->longitude,
                    'statut' => $r->statut,
                    'created_at' => $r->created_at,
                    'citizen_nom' => $r->citizen->nom ?? '',
                    'citizen_telephone' => $r->citizen->telephone ?? '',
                ];
            });

        return response()->json(['reports' => $reports]);
    }
}
