<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Household;
use App\Models\Member;
use App\Models\Report;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ZoneScope;

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $q = $request->q;
        $user = $request->user();
        $limit = 10;

        $users = $this->searchUsers($q, $user, $limit);
        $households = $this->searchHouseholds($q, $user, $limit);
        $announcements = $this->searchAnnouncements($q, $limit);
        $reports = $this->searchReports($q, $user, $limit);
        $members = $this->searchMembers($q, $user, $limit);

        $total = count($users) + count($households) + count($announcements) + count($reports) + count($members);

        return response()->json([
            'query' => $q,
            'total' => $total,
            'results' => [
                'users' => $users,
                'households' => $households,
                'announcements' => $announcements,
                'reports' => $reports,
                'members' => $members,
            ],
        ]);
    }

    private function searchUsers(string $q, User $user, int $limit): array
    {
        $query = User::select('id', 'nom', 'telephone', 'email', 'role', 'photo_profil')
            ->where(function ($qb) use ($q) {
                $qb->where('nom', 'LIKE', "%{$q}%")
                   ->orWhere('telephone', 'LIKE', "%{$q}%")
                   ->orWhere('email', 'LIKE', "%{$q}%");
            });

        $userIds = $this->getAccessibleUserIds($user);
        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }

        return $query->limit($limit)->get()->toArray();
    }

    private function searchHouseholds(string $q, User $user, int $limit): array
    {
        $query = Household::select('households.id', 'households.quartier', 'households.adresse', 'users.nom as chef_nom')
            ->join('users', 'households.chef_id', '=', 'users.id')
            ->where(function ($qb) use ($q) {
                $qb->where('households.quartier', 'LIKE', "%{$q}%")
                   ->orWhere('households.adresse', 'LIKE', "%{$q}%")
                   ->orWhere('users.nom', 'LIKE', "%{$q}%")
                   ->orWhere('users.telephone', 'LIKE', "%{$q}%");
            });

        $this->applyHouseholdZoneFilter($query, $user);

        return $query->limit($limit)->get()->toArray();
    }

    private function searchAnnouncements(string $q, int $limit): array
    {
        return Announcement::select('id', 'titre', 'autorite', 'date')
            ->where(function ($qb) use ($q) {
                $qb->where('titre', 'LIKE', "%{$q}%")
                   ->orWhere('contenu', 'LIKE', "%{$q}%")
                   ->orWhere('autorite', 'LIKE', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchReports(string $q, User $user, int $limit): array
    {
        $query = Report::select('reports.id', 'reports.description', 'reports.statut', 'reports.created_at', 'users.nom as citizen_nom')
            ->join('users', 'reports.citizen_id', '=', 'users.id')
            ->where(function ($qb) use ($q) {
                $qb->where('reports.description', 'LIKE', "%{$q}%")
                   ->orWhere('users.nom', 'LIKE', "%{$q}%");
            });

        $userIds = $this->getAccessibleUserIds($user);
        if ($userIds !== null) {
            $query->whereIn('reports.citizen_id', $userIds);
        }

        return $query->orderByDesc('reports.created_at')->limit($limit)->get()->toArray();
    }

    private function searchMembers(string $q, User $user, int $limit): array
    {
        $query = Member::select('members.id', 'members.nom', 'members.type', 'members.telephone', 'households.quartier', 'users.nom as chef_nom')
            ->join('households', 'members.household_id', '=', 'households.id')
            ->join('users', 'households.chef_id', '=', 'users.id')
            ->where(function ($qb) use ($q) {
                $qb->where('members.nom', 'LIKE', "%{$q}%")
                   ->orWhere('members.telephone', 'LIKE', "%{$q}%");
            });

        $householdIds = $this->getAccessibleHouseholdIds($user);
        if ($householdIds !== null) {
            $query->whereIn('members.household_id', $householdIds);
        }

        return $query->limit($limit)->get()->toArray();
    }
}
