<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ZoneScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $household = Household::where('chef_id', $user->id)->first();

        if (!$household) {
            return response()->json([
                'payments' => [],
                'message' => 'Aucun ménage associé à votre compte.',
            ]);
        }

        $payments = Payment::where('household_id', $household->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['payments' => $payments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'motif' => 'required|string|max:100',
            'motif_autre' => 'nullable|string|max:255',
            'montant' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
            'recu_photo' => 'required|image|max:5120',
        ]);

        $user = $request->user();
        $household = Household::where('chef_id', $user->id)->firstOrFail();

        $recuPath = $request->file('recu_photo')->store('recus', 'public');
        $recuPath = '/storage/' . $recuPath;

        Payment::create([
            'household_id' => $household->id,
            'motif' => $request->motif,
            'motif_autre' => $request->motif_autre,
            'montant' => $request->montant,
            'date_paiement' => $request->date_paiement,
            'recu_photo' => $recuPath,
            'statut_validation' => 'en_attente',
        ]);

        // Notifier les autorités de la même zone (collinaire et au-dessus)
        $authoritiesQuery = User::whereIn('role', User::AUTHORITY_ROLES);
        if ($household->geographic_area_id) {
            $allAncestorIds = $this->getAncestorAreaIds($household->geographic_area_id);
            $relevantIds = array_merge([$household->geographic_area_id], $allAncestorIds);
            $authoritiesQuery->where(function ($q) use ($relevantIds) {
                $q->whereIn('geographic_area_id', $relevantIds)
                  ->orWhereNull('geographic_area_id');
            });
        }

        foreach ($authoritiesQuery->pluck('id') as $authorityId) {
            Notification::create([
                'user_id' => $authorityId,
                'type' => 'nouveau_paiement',
                'titre' => 'Nouveau Paiement',
                'message' => "Le citoyen {$user->nom} a enregistré un paiement pour " . ($request->motif === 'autre' ? $request->motif_autre : $request->motif) . ".",
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Paiement enregistré avec succès'], 201);
    }

    public function validate_payment(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:valider,rejeter']);

        $payment = Payment::with('household')->findOrFail($id);
        $user = $request->user();

        // Vérifier que le paiement est dans la zone de l'utilisateur
        $householdIds = $this->getAccessibleHouseholdIds($user);
        if ($householdIds !== null && !in_array($payment->household_id, $householdIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement n\'est pas dans votre zone.',
            ], 403);
        }

        $newStatus = $request->action === 'valider' ? 'valide' : 'rejete';
        $payment->update(['statut_validation' => $newStatus]);

        $motifText = $payment->motif === 'autre' ? $payment->motif_autre : $payment->motif;
        Notification::create([
            'user_id' => $payment->household->chef_id,
            'type' => 'validation_paiement',
            'titre' => 'Statut de Paiement',
            'message' => "Votre paiement pour {$motifText} a été " . ($newStatus === 'valide' ? 'validé' : 'rejeté') . '.',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Paiement " . ($newStatus === 'valide' ? 'validé' : 'rejeté') . " avec succès.",
        ]);
    }

    /**
     * Retourne les IDs ancêtres d'une zone (pour notifier les chefs de zones parentes).
     */
    private function getAncestorAreaIds(int $areaId): array
    {
        $ids = [];
        $area = \App\Models\GeographicArea::find($areaId);

        while ($area && $area->parent_id) {
            $ids[] = $area->parent_id;
            $area = \App\Models\GeographicArea::find($area->parent_id);
        }

        return $ids;
    }
}
