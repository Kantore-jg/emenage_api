<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de Zone - {{ $zoneName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header { background: #2c5f2d; color: white; padding: 20px 30px; text-align: center; }
        .header h1 { font-size: 22px; margin-bottom: 4px; }
        .header p { font-size: 12px; opacity: 0.9; }
        .subtitle { background: #97bc62; color: white; padding: 8px 30px; font-size: 13px; text-align: center; }
        .content { padding: 20px 30px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 10px; color: #666; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .meta-table td { padding: 4px 8px; font-size: 10px; color: #666; }
        .meta-table td:first-child { text-align: left; }
        .meta-table td:last-child { text-align: right; }
        .section-title { background: #f0f0f0; padding: 8px 12px; font-size: 14px; font-weight: bold; color: #2c5f2d; margin: 20px 0 10px; border-left: 4px solid #2c5f2d; }
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stats-table th { background: #2c5f2d; color: white; padding: 8px 12px; text-align: left; font-size: 11px; }
        .stats-table td { padding: 8px 12px; border-bottom: 1px solid #eee; font-size: 11px; }
        .stats-table tr:nth-child(even) { background: #f9f9f9; }
        .number { text-align: right; font-weight: bold; }
        .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-grid td { width: 25%; padding: 12px; text-align: center; border: 1px solid #eee; }
        .summary-grid .big-number { font-size: 24px; font-weight: bold; color: #2c5f2d; }
        .summary-grid .label { font-size: 10px; color: #666; margin-top: 4px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid #2c5f2d; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RÉPUBLIQUE DU BURUNDI</h1>
        <p>Menage - Système de Gestion des Ménages</p>
    </div>
    <div class="subtitle">
        RAPPORT SYNTHÉTIQUE - {{ strtoupper($zoneName) }}
    </div>

    <div class="content">
        <table class="meta-table">
            <tr>
                <td><strong>Période :</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</td>
                <td><strong>Généré par :</strong> {{ $generatedBy }} | {{ $generatedAt }}</td>
            </tr>
        </table>

        <table class="summary-grid">
            <tr>
                <td>
                    <div class="big-number">{{ $totalHouseholds }}</div>
                    <div class="label">Ménages</div>
                </td>
                <td>
                    <div class="big-number">{{ $totalMembers }}</div>
                    <div class="label">Membres</div>
                </td>
                <td>
                    <div class="big-number">{{ $totalPayments }}</div>
                    <div class="label">Paiements</div>
                </td>
                <td>
                    <div class="big-number">{{ $totalReports }}</div>
                    <div class="label">Signalements</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Population et Ménages</div>
        <table class="stats-table">
            <thead><tr><th>Indicateur</th><th style="text-align:right;">Valeur</th></tr></thead>
            <tbody>
                <tr><td>Nouveaux utilisateurs inscrits (période)</td><td class="number">{{ $totalUsers }}</td></tr>
                <tr><td>Nombre total de ménages</td><td class="number">{{ $totalHouseholds }}</td></tr>
                <tr><td>Membres permanents</td><td class="number">{{ $totalMembers }}</td></tr>
                <tr><td>  — dont validés</td><td class="number">{{ $membersValidated }}</td></tr>
                <tr><td>  — dont en attente</td><td class="number">{{ $membersPending }}</td></tr>
                <tr><td>Invités présents</td><td class="number">{{ $totalGuests }}</td></tr>
            </tbody>
        </table>

        <div class="section-title">Paiements (période)</div>
        <table class="stats-table">
            <thead><tr><th>Indicateur</th><th style="text-align:right;">Valeur</th></tr></thead>
            <tbody>
                <tr><td>Total des paiements</td><td class="number">{{ $totalPayments }}</td></tr>
                <tr><td>Montant total</td><td class="number">{{ $totalAmount }} FBU</td></tr>
                <tr><td>  — validés</td><td class="number">{{ $paymentsValidated }}</td></tr>
                <tr><td>  — en attente</td><td class="number">{{ $paymentsPending }}</td></tr>
            </tbody>
        </table>

        @if($paymentsByType->count())
        <div class="section-title">Répartition par Type de Paiement</div>
        <table class="stats-table">
            <thead><tr><th>Type</th><th style="text-align:right;">Nombre</th><th style="text-align:right;">Montant (FBU)</th></tr></thead>
            <tbody>
                @foreach($paymentsByType as $pt)
                <tr>
                    <td>{{ ucfirst($pt->type_paiement) }}</td>
                    <td class="number">{{ $pt->total }}</td>
                    <td class="number">{{ number_format($pt->montant_total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="section-title">Signalements / Incidents (période)</div>
        <table class="stats-table">
            <thead><tr><th>Indicateur</th><th style="text-align:right;">Valeur</th></tr></thead>
            <tbody>
                <tr><td>Total des signalements</td><td class="number">{{ $totalReports }}</td></tr>
                <tr><td>  — en attente</td><td class="number">{{ $reportsPending }}</td></tr>
                <tr><td>  — en cours</td><td class="number">{{ $reportsInProgress }}</td></tr>
                <tr><td>  — résolus</td><td class="number">{{ $reportsResolved }}</td></tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Document généré automatiquement par Menage &copy; {{ date('Y') }}</p>
            <p>Ce document est strictement confidentiel et réservé aux autorités compétentes.</p>
        </div>
    </div>
</body>
</html>
