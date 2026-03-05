<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function storeMember(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'telephone' => 'nullable|string|max:20',
            'photo_cni' => 'nullable|image|max:5120',
        ]);

        $household = $request->user()->household;
        $age = (int) $request->age;

        if ($age > 18 && !$request->hasFile('photo_cni')) {
            return response()->json([
                'success' => false,
                'message' => 'La photo CNI est obligatoire pour les personnes de plus de 18 ans',
            ], 400);
        }

        $photoCni = null;
        if ($request->hasFile('photo_cni')) {
            $photoCni = $request->file('photo_cni')->store('cni', 'public');
            $photoCni = '/storage/' . $photoCni;
        }

        Member::create([
            'household_id' => $household->id,
            'nom' => trim($request->nom),
            'type' => 'permanent',
            'age' => $age,
            'telephone' => $request->telephone ? trim($request->telephone) : null,
            'photo_cni' => $photoCni,
            'statut_validation' => 'en_attente',
        ]);

        $this->notifyChefQuartier(
            'nouveau_membre',
            'Nouveau membre',
            "Un nouveau membre permanent a été ajouté dans le quartier {$household->quartier}"
        );

        return response()->json(['success' => true, 'message' => 'Membre ajouté avec succès']);
    }

    public function storeInvite(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'telephone' => 'nullable|string|max:20',
        ]);

        $household = $request->user()->household;

        Member::create([
            'household_id' => $household->id,
            'nom' => trim($request->nom),
            'type' => 'invite',
            'age' => (int) $request->age,
            'telephone' => $request->telephone ? trim($request->telephone) : null,
            'statut' => 'present',
            'statut_validation' => 'en_attente',
        ]);

        $this->notifyChefQuartier(
            'nouvel_invite',
            'Nouvel invité',
            "Un nouvel invité a été enregistré dans le quartier {$household->quartier}"
        );

        return response()->json(['success' => true, 'message' => 'Invité ajouté avec succès']);
    }

    public function updateInvite(Request $request, $id)
    {
        $household = $request->user()->household;
        $member = Member::where('id', $id)
            ->where('household_id', $household->id)
            ->where('type', 'invite')
            ->firstOrFail();

        $request->validate(['statut' => 'required|in:present,parti']);
        $member->update(['statut' => $request->statut]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    }

    public function destroy(Request $request, $id)
    {
        $household = $request->user()->household;
        $member = Member::where('id', $id)
            ->where('household_id', $household->id)
            ->firstOrFail();

        $member->delete();

        return response()->json(['success' => true, 'message' => 'Membre supprimé avec succès']);
    }

    private function notifyChefQuartier(string $type, string $titre, string $message): void
    {
        $chefs = User::where('role', 'chef_quartier')->pluck('id');
        foreach ($chefs as $chefId) {
            Notification::create([
                'user_id' => $chefId,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
            ]);
        }
    }
}
