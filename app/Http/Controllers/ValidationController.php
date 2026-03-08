<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Member;
use App\Models\Notification;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    use ZoneScope;

    public function validateMember(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:valider,rejeter']);

        $member = Member::findOrFail($id);
        $user = $request->user();

        // Vérifier que le membre est dans la zone de l'utilisateur
        $householdIds = $this->getAccessibleHouseholdIds($user);
        if ($householdIds !== null && !in_array($member->household_id, $householdIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce membre n\'est pas dans votre zone géographique.',
            ], 403);
        }

        $statutValidation = $request->action === 'valider' ? 'valide' : 'rejete';
        $member->update(['statut_validation' => $statutValidation]);

        $household = Household::find($member->household_id);
        if ($household) {
            $message = $request->action === 'valider'
                ? "Votre enregistrement de {$member->nom} a été validé par les autorités."
                : "Votre enregistrement de {$member->nom} a été rejeté par les autorités.";

            Notification::create([
                'user_id' => $household->chef_id,
                'type' => 'validation',
                'titre' => $request->action === 'valider' ? 'Enregistrement validé' : 'Enregistrement rejeté',
                'message' => $message,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $request->action === 'valider' ? 'Enregistrement validé avec succès' : 'Enregistrement rejeté',
        ]);
    }

    public function pending(Request $request)
    {
        $user = $request->user();
        $householdIds = $this->getAccessibleHouseholdIds($user);

        $query = Member::with(['household.chef:id,nom,telephone'])
            ->where('statut_validation', 'en_attente')
            ->orderByDesc('created_at');

        if ($householdIds !== null) {
            $query->whereIn('household_id', $householdIds);
        }

        $members = $query->get()->map(function ($m) {
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
                'chef_telephone' => $m->household->chef->telephone ?? '',
            ];
        });

        return response()->json($members);
    }
}
