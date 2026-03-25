<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Report;
use App\Models\User;
use App\Traits\ZoneScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    use ZoneScope;

    public function zoneReport(Request $request)
    {
        $user = $request->user();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $userIds = $this->getAccessibleUserIds($user);
        $householdIds = $this->getAccessibleHouseholdIds($user);

        $zoneName = 'Tout le pays';
        if ($user->geographicArea) {
            $user->load('geographicArea');
            $zoneName = $user->geographicArea->name;
        }

        $totalUsers = $userIds === null
            ? User::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->count()
            : User::whereIn('id', $userIds)->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->count();

        $totalHouseholds = $householdIds === null
            ? Household::count()
            : Household::whereIn('id', $householdIds)->count();

        $totalMembers = $householdIds === null
            ? Member::where('type', 'permanent')->count()
            : Member::where('type', 'permanent')->whereIn('household_id', $householdIds)->count();

        $membersValidated = $householdIds === null
            ? Member::where('type', 'permanent')->where('statut_validation', 'valide')->count()
            : Member::where('type', 'permanent')->where('statut_validation', 'valide')->whereIn('household_id', $householdIds)->count();

        $membersPending = $householdIds === null
            ? Member::where('type', 'permanent')->where('statut_validation', 'en_attente')->count()
            : Member::where('type', 'permanent')->where('statut_validation', 'en_attente')->whereIn('household_id', $householdIds)->count();

        $totalGuests = $householdIds === null
            ? Member::where('type', 'invite')->where('statut', 'present')->count()
            : Member::where('type', 'invite')->where('statut', 'present')->whereIn('household_id', $householdIds)->count();

        $paymentsQuery = Payment::query();
        if ($householdIds !== null) {
            $paymentsQuery->whereIn('household_id', $householdIds);
        }
        $paymentsInPeriod = (clone $paymentsQuery)->whereBetween('date_paiement', [$dateFrom, $dateTo]);
        $totalPayments = $paymentsInPeriod->count();
        $totalAmount = $paymentsInPeriod->sum('montant');
        $paymentsValidated = (clone $paymentsQuery)->whereBetween('date_paiement', [$dateFrom, $dateTo])->where('statut_validation', 'valide')->count();
        $paymentsPending = (clone $paymentsQuery)->whereBetween('date_paiement', [$dateFrom, $dateTo])->where('statut_validation', 'en_attente')->count();

        $reportsQuery = Report::query();
        if ($userIds !== null) {
            $reportsQuery->whereIn('citizen_id', $userIds);
        }
        $reportsInPeriod = (clone $reportsQuery)->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
        $totalReports = $reportsInPeriod->count();
        $reportsPending = (clone $reportsQuery)->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->where('statut', 'en_attente')->count();
        $reportsInProgress = (clone $reportsQuery)->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->where('statut', 'en_cours')->count();
        $reportsResolved = (clone $reportsQuery)->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->where('statut', 'resolu')->count();

        $paymentsByType = Payment::query()
            ->when($householdIds !== null, fn($q) => $q->whereIn('household_id', $householdIds))
            ->whereBetween('date_paiement', [$dateFrom, $dateTo])
            ->selectRaw("CASE WHEN motif = 'autre' THEN motif_autre ELSE motif END as type_paiement")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(montant) as montant_total')
            ->groupBy('type_paiement')
            ->get();

        $data = [
            'zoneName' => $zoneName,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generatedBy' => $user->nom,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'totalUsers' => $totalUsers,
            'totalHouseholds' => $totalHouseholds,
            'totalMembers' => $totalMembers,
            'membersValidated' => $membersValidated,
            'membersPending' => $membersPending,
            'totalGuests' => $totalGuests,
            'totalPayments' => $totalPayments,
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'paymentsValidated' => $paymentsValidated,
            'paymentsPending' => $paymentsPending,
            'totalReports' => $totalReports,
            'reportsPending' => $reportsPending,
            'reportsInProgress' => $reportsInProgress,
            'reportsResolved' => $reportsResolved,
            'paymentsByType' => $paymentsByType,
        ];

        $pdf = Pdf::loadView('exports.zone-report', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'rapport-' . str_replace(' ', '-', strtolower($zoneName)) . '-' . $dateFrom . '-' . $dateTo . '.pdf';

        return $pdf->download($filename);
    }
}
