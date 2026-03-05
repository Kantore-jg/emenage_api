<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'users_by_month' => $this->usersByMonth(),
            'payments_by_type' => $this->paymentsByType(),
            'members_by_household' => $this->membersByHousehold(),
            'summary' => $this->summary(),
        ]);
    }

    private function usersByMonth(): array
    {
        $rows = User::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

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

    private function paymentsByType(): array
    {
        $rows = Payment::select(
                DB::raw("CASE WHEN motif = 'autre' THEN 'Autre' ELSE motif END as type_paiement"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(montant) as montant_total')
            )
            ->groupBy('type_paiement')
            ->get();

        return [
            'labels' => $rows->pluck('type_paiement')->toArray(),
            'counts' => $rows->pluck('total')->toArray(),
            'amounts' => $rows->pluck('montant_total')->toArray(),
        ];
    }

    private function membersByHousehold(): array
    {
        $rows = Household::select(
                'households.id',
                DB::raw("CONCAT(users.nom, ' - ', households.quartier) as label"),
                DB::raw('COUNT(members.id) as total_membres')
            )
            ->join('users', 'users.id', '=', 'households.chef_id')
            ->leftJoin('members', 'members.household_id', '=', 'households.id')
            ->groupBy('households.id', 'users.nom', 'households.quartier')
            ->orderByDesc('total_membres')
            ->limit(15)
            ->get();

        return [
            'labels' => $rows->pluck('label')->toArray(),
            'data' => $rows->pluck('total_membres')->toArray(),
        ];
    }

    private function summary(): array
    {
        return [
            'total_users' => User::count(),
            'total_citoyens' => User::where('role', 'citoyen')->count(),
            'total_households' => Household::count(),
            'total_payments' => Payment::count(),
            'total_members' => Member::count(),
        ];
    }
}
