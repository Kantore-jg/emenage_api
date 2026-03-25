<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportPhoto;
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
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $report = Report::create([
            'citizen_id' => $request->user()->id,
            'description' => trim($request->description),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $path = $file->store('reports', 'public');
                ReportPhoto::create([
                    'report_id' => $report->id,
                    'path' => $path,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Signalement créé avec succès'], 201);
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate(['statut' => 'required|in:en_attente,en_cours,resolu']);

        $report = Report::findOrFail($id);

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

        $query = Report::with(['citizen:id,nom', 'photos'])
            ->orderByDesc('created_at');

        if ($userIds !== null) {
            $query->whereIn('citizen_id', $userIds);
        }
        if ($request->statut) {
            $query->where('statut', $request->statut);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%");
            });
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->input('per_page', 15);
        $paginated = $query->paginate($perPage)->through(function ($r) {
            return [
                'id' => $r->id,
                'description' => $r->description,
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'statut' => $r->statut,
                'created_at' => $r->created_at,
                'citizen_nom' => $r->citizen->nom ?? '',
                'photos' => $r->photos->map(fn($p) => [
                    'id' => $p->id,
                    'url' => asset('storage/' . $p->path),
                ]),
            ];
        });

        return response()->json([
            'reports' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
        ]);
    }
}
