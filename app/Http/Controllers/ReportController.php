<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
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
        $report->update(['statut' => $request->statut]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    }

    public function all()
    {
        $reports = Report::with('citizen:id,nom')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
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
