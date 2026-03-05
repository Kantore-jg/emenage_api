<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
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

        $chefs = User::where('role', 'chef_quartier')->pluck('id');
        foreach ($chefs as $chefId) {
            Notification::create([
                'user_id' => $chefId,
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
}
