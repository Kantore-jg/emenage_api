<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    use ZoneScope;

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'users_by_month' => $this->usersByMonth($user),
            'payments_by_type' => $this->paymentsByType($user),
            'members_by_household' => $this->membersByHousehold($user),
            'summary' => $this->summary($user),
        ]);
    }

    private function usersByMonth(User $user): array
    {
        $query = User::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth());

        $userIds = $this->getAccessibleUserIds($user);
        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }

        $rows = $query->groupBy('mois')->orderBy('mois')->get();

        $labels = [];
        $data = [];
        $months = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];

        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $key = $d->format('Y-m');
            $labels[] = $months[$d->month - 1] . ' ' . $d->format('Y');
            $found = $rows->firstWhere('mois', $key);
            $data[] = $found ? $found->total : 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function paymentsByType(User $user): array
    {
        $query = Payment::select(
                DB::raw("CASE WHEN motif = 'autre' THEN 'Autre' ELSE motif END as type_paiement"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(montant) as montant_total')
            );

        $householdIds = $this->getAccessibleHouseholdIds($user);
        if ($householdIds !== null) {
            $query->whereIn('household_id', $householdIds);
        }

        $rows = $query->groupBy('type_paiement')->get();

        return [
            'labels' => $rows->pluck('type_paiement')->toArray(),
            'counts' => $rows->pluck('total')->toArray(),
            'amounts' => $rows->pluck('montant_total')->toArray(),
        ];
    }

    private function membersByHousehold(User $user): array
    {
        $query = Household::select(
                'households.id',
                DB::raw("CONCAT(users.nom, ' - ', households.quartier) as label"),
                DB::raw('COUNT(members.id) as total_membres')
            )
            ->join('users', 'users.id', '=', 'households.chef_id')
            ->leftJoin('members', 'members.household_id', '=', 'households.id')
            ->groupBy('households.id', 'users.nom', 'households.quartier')
            ->orderByDesc('total_membres')
            ->limit(15);

        $this->applyHouseholdZoneFilter($query, $user);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->toArray(),
            'data' => $rows->pluck('total_membres')->toArray(),
        ];
    }

    private function summary(User $user): array
    {
        $userIds = $this->getAccessibleUserIds($user);
        $householdIds = $this->getAccessibleHouseholdIds($user);

        $totalUsers = $userIds === null ? User::count() : User::whereIn('id', $userIds)->count();
        $totalCitoyens = $userIds === null
            ? User::where('role', 'citoyen')->count()
            : User::where('role', 'citoyen')->whereIn('id', $userIds)->count();
        $totalHouseholds = $householdIds === null ? Household::count() : Household::whereIn('id', $householdIds)->count();
        $totalPayments = $householdIds === null ? Payment::count() : Payment::whereIn('household_id', $householdIds)->count();
        $totalMembers = $householdIds === null ? Member::count() : Member::whereIn('household_id', $householdIds)->count();

        return [
            'total_users' => $totalUsers,
            'total_citoyens' => $totalCitoyens,
            'total_households' => $totalHouseholds,
            'total_payments' => $totalPayments,
            'total_members' => $totalMembers,
        ];
    }
}
