<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ZoneScope;

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        Report::create([
            'citizen_id' => $request->user()->id,
            'description' => trim($request->description),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json(['success' => true, 'message' => 'Signalement créé avec succès'], 201);
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate(['statut' => 'required|in:en_attente,en_cours,resolu']);

        $report = Report::findOrFail($id);

        // Vérifier que le signalement est dans la zone de l'utilisateur
        $userIds = $this->getAccessibleUserIds($request->user());
        if ($userIds !== null && !in_array($report->citizen_id, $userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce signalement n\'est pas dans votre zone.',
            ], 403);
        }

        $report->update(['statut' => $request->statut]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    }

    public function all(Request $request)
    {
        $user = $request->user();
        $userIds = $this->getAccessibleUserIds($user);

        $query = Report::with('citizen:id,nom')
            ->orderByDesc('created_at');

        if ($userIds !== null) {
            $query->whereIn('citizen_id', $userIds);
        }

        $reports = $query->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'description' => $r->description,
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'statut' => $r->statut,
                'created_at' => $r->created_at,
                'citizen_nom' => $r->citizen->nom ?? '',
            ];
        });

        return response()->json($reports);
    }
}
