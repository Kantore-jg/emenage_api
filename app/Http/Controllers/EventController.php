<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ZoneScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Event::with(['creator:id,nom', 'geographicArea:id,name'])
            ->orderBy('date_debut');

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->month) {
            $query->whereMonth('date_debut', $request->month);
        }
        if ($request->year) {
            $query->whereYear('date_debut', $request->year);
        }

        $areaId = $user->geographic_area_id;
        if ($areaId) {
            $query->where(function ($q) use ($areaId) {
                $q->where('geographic_area_id', $areaId)
                  ->orWhereNull('geographic_area_id');
            });
        }

        $events = $query->get();

        return response()->json(['events' => $events]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string|max:255',
            'type' => 'required|in:reunion,vaccination,marche,ceremonie,sport,formation,autre',
            'geographic_area_id' => 'nullable|exists:geographic_areas,id',
            'announcement_id' => 'nullable|exists:announcements,id',
        ]);

        $event = Event::create([
            ...$request->only(['titre', 'description', 'date_debut', 'date_fin', 'lieu', 'type', 'geographic_area_id', 'announcement_id']),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Événement créé avec succès',
            'event' => $event,
        ], 201);
    }

    public function show($id)
    {
        $event = Event::with(['creator:id,nom', 'geographicArea:id,name', 'announcement:id,titre'])
            ->findOrFail($id);

        return response()->json(['event' => $event]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->created_by !== $request->user()->id && !in_array($request->user()->role, ['admin', 'ministere'])) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string|max:255',
            'type' => 'sometimes|in:reunion,vaccination,marche,ceremonie,sport,formation,autre',
            'geographic_area_id' => 'nullable|exists:geographic_areas,id',
            'announcement_id' => 'nullable|exists:announcements,id',
        ]);

        $event->update($request->only(['titre', 'description', 'date_debut', 'date_fin', 'lieu', 'type', 'geographic_area_id', 'announcement_id']));

        return response()->json(['success' => true, 'message' => 'Événement mis à jour', 'event' => $event]);
    }

    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->created_by !== $request->user()->id && !in_array($request->user()->role, ['admin', 'ministere'])) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $event->delete();

        return response()->json(['success' => true, 'message' => 'Événement supprimé']);
    }
}
