<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusField;
use App\Traits\ZoneScope;
use Illuminate\Http\Request;

class CensusController extends Controller
{
    use ZoneScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Census::with(['creator:id,nom', 'geographicArea:id,name'])
            ->withCount(['fields', 'responses', 'agents']);

        $areaIds = $this->getZoneIds($user);
        if ($areaIds !== null) {
            $query->where(function ($q) use ($areaIds) {
                $q->whereIn('geographic_area_id', $areaIds)
                  ->orWhereNull('geographic_area_id');
            });
        }

        if ($request->statut) {
            $query->where('statut', $request->statut);
        }

        $censuses = $query->orderByDesc('created_at')->get();

        return response()->json(['censuses' => $censuses]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'geographic_area_id' => 'nullable|exists:geographic_areas,id',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|in:text,number,date,select,multi_select,boolean,textarea',
            'fields.*.options' => 'nullable|array',
            'fields.*.required' => 'boolean',
        ]);

        $census = Census::create([
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'geographic_area_id' => $request->geographic_area_id,
            'created_by' => $request->user()->id,
            'statut' => 'brouillon',
        ]);

        foreach ($request->fields as $index => $fieldData) {
            CensusField::create([
                'census_id' => $census->id,
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'options' => $fieldData['options'] ?? null,
                'required' => $fieldData['required'] ?? false,
                'field_order' => $index,
            ]);
        }

        $census->load(['fields', 'geographicArea:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Recensement créé avec succès',
            'census' => $census,
        ], 201);
    }

    public function show($id)
    {
        $census = Census::with([
            'fields',
            'creator:id,nom',
            'geographicArea:id,name',
            'agents.user:id,nom,telephone',
            'agents.geographicArea:id,name',
        ])->withCount('responses')->findOrFail($id);

        return response()->json(['census' => $census]);
    }

    public function update(Request $request, $id)
    {
        $census = Census::findOrFail($id);

        $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'statut' => 'sometimes|in:brouillon,actif,termine,archive',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'geographic_area_id' => 'nullable|exists:geographic_areas,id',
        ]);

        $census->update($request->only([
            'titre', 'description', 'statut', 'date_debut', 'date_fin', 'geographic_area_id',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Recensement mis à jour',
            'census' => $census,
        ]);
    }

    /**
     * Mettre à jour les champs du formulaire (remplace tout).
     */
    public function updateFields(Request $request, $id)
    {
        $census = Census::findOrFail($id);

        if ($census->statut !== 'brouillon') {
            return response()->json([
                'success' => false,
                'message' => 'Les champs ne peuvent être modifiés que pour un recensement en brouillon.',
            ], 422);
        }

        $request->validate([
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|in:text,number,date,select,multi_select,boolean,textarea',
            'fields.*.options' => 'nullable|array',
            'fields.*.required' => 'boolean',
        ]);

        $census->fields()->delete();

        foreach ($request->fields as $index => $fieldData) {
            CensusField::create([
                'census_id' => $census->id,
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'options' => $fieldData['options'] ?? null,
                'required' => $fieldData['required'] ?? false,
                'field_order' => $index,
            ]);
        }

        $census->load('fields');

        return response()->json([
            'success' => true,
            'message' => 'Champs mis à jour',
            'census' => $census,
        ]);
    }

    public function destroy($id)
    {
        $census = Census::findOrFail($id);
        $census->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recensement supprimé',
        ]);
    }
}
