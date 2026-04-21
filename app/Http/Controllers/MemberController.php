<?php

namespace App\Http\Controllers;

use App\Models\GeographicArea;
use App\Models\Household;
use App\Models\Member;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class MemberController extends Controller
{
    use ZoneScope;

    public function storeMember(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'telephone' => 'nullable|string|max:20',
            'photo_cni' => 'nullable|image|max:5120',
            'household_id' => 'nullable|integer|exists:households,id',
        ]);

        $household = $this->resolveTargetHousehold($request);
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

        $this->notifyAuthorities(
            $household,
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
            'household_id' => 'nullable|integer|exists:households,id',
        ]);

        $household = $this->resolveTargetHousehold($request);

        Member::create([
            'household_id' => $household->id,
            'nom' => trim($request->nom),
            'type' => 'invite',
            'age' => (int) $request->age,
            'telephone' => $request->telephone ? trim($request->telephone) : null,
            'statut' => 'present',
            'statut_validation' => 'en_attente',
        ]);

        $this->notifyAuthorities(
            $household,
            'nouvel_invite',
            'Nouvel invité',
            "Un nouvel invité a été enregistré dans le quartier {$household->quartier}"
        );

        return response()->json(['success' => true, 'message' => 'Invité ajouté avec succès']);
    }

    public function updateInvite(Request $request, $id)
    {
        $request->validate(['statut' => 'required|in:present,parti']);

        $member = Member::where('id', $id)
            ->where('type', 'invite')
            ->firstOrFail();

        $this->authorizeMemberAccess($request, $member);
        $member->update(['statut' => $request->statut]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    }

    public function destroy(Request $request, $id)
    {
        $member = Member::findOrFail($id);
        $this->authorizeMemberAccess($request, $member);

        $member->delete();

        return response()->json(['success' => true, 'message' => 'Membre supprimé avec succès']);
    }

    private function resolveTargetHousehold(Request $request): Household
    {
        $user = $request->user();

        if ($user->isAuthority()) {
            $householdId = $request->input('household_id');

            if (!$householdId) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'Le ménage cible est obligatoire pour cette opération.',
                ], 422));
            }

            $household = Household::findOrFail($householdId);
            $this->authorizeHouseholdAccess($user, $household);

            return $household;
        }

        $household = $user->household;
        if (!$household) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Aucun ménage associé à votre compte.',
            ], 403));
        }

        return $household;
    }

    private function authorizeMemberAccess(Request $request, Member $member): void
    {
        $member->loadMissing('household');

        if (!$member->household) {
            abort(404, 'Ménage introuvable pour ce membre.');
        }

        $this->authorizeHouseholdAccess($request->user(), $member->household);
    }

    private function authorizeHouseholdAccess(User $user, Household $household): void
    {
        if ($user->isAuthority()) {
            $areaIds = $this->getZoneIds($user);

            if ($areaIds !== null && !in_array($household->geographic_area_id, $areaIds)) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Ce ménage n\'est pas dans votre zone.',
                ], 403));
            }

            return;
        }

        if (!$user->household || $user->household->id !== $household->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Accès refusé pour ce ménage.',
            ], 403));
        }
    }

    /**
     * Notifie les autorités de la zone du ménage (collinaire, zonal, etc.)
     */
    private function notifyAuthorities($household, string $type, string $titre, string $message): void
    {
        $query = User::whereIn('role', User::AUTHORITY_ROLES);

        if ($household->geographic_area_id) {
            $area = GeographicArea::find($household->geographic_area_id);
            $ancestorIds = $area ? $area->ancestors()->pluck('id')->toArray() : [];
            $relevantIds = array_merge([$household->geographic_area_id], $ancestorIds);

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
