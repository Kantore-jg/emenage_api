<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusAgent;
use App\Models\CensusResponse;
use App\Models\CensusResponseValue;
use Illuminate\Http\Request;

class CensusCollectionController extends Controller
{
    /**
     * Agent: voir les campagnes auxquelles il est assigné.
     */
    public function myCensuses(Request $request)
    {
        $user = $request->user();

        $censusIds = CensusAgent::where('user_id', $user->id)->pluck('census_id');

        $censuses = Census::with(['fields', 'geographicArea:id,name'])
            ->whereIn('id', $censusIds)
            ->where('statut', 'actif')
            ->withCount('responses')
            ->get()
            ->map(function ($census) use ($user) {
                $assignment = CensusAgent::where('census_id', $census->id)
                    ->where('user_id', $user->id)
                    ->with('geographicArea:id,name')
                    ->first();

                $myResponses = CensusResponse::where('census_id', $census->id)
                    ->where('agent_id', $user->id)
                    ->count();

                $census->assigned_zone = $assignment?->geographicArea;
                $census->my_responses_count = $myResponses;
                return $census;
            });

        return response()->json(['censuses' => $censuses]);
    }

    /**
     * Agent: voir le formulaire d'un recensement (champs à remplir).
     */
    public function form($censusId, Request $request)
    {
        $user = $request->user();

        $agent = CensusAgent::where('census_id', $censusId)
            ->where('user_id', $user->id)
            ->with('geographicArea:id,name')
            ->firstOrFail();

        $census = Census::with('fields')->findOrFail($censusId);

        if (!$census->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce recensement n\'est pas actif.',
            ], 422);
        }

        return response()->json([
            'census' => $census,
            'assigned_zone' => $agent->geographicArea,
        ]);
    }

    /**
     * Agent: soumettre une réponse collectée sur le terrain.
     */
    public function submit(Request $request, $censusId)
    {
        $user = $request->user();

        $agent = CensusAgent::where('census_id', $censusId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $census = Census::with('fields')->findOrFail($censusId);

        if (!$census->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce recensement n\'est pas actif.',
            ], 422);
        }

        $request->validate([
            'respondent_name' => 'nullable|string|max:255',
            'respondent_phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'values' => 'required|array',
        ]);

        // Valider les champs requis
        foreach ($census->fields as $field) {
            if ($field->required) {
                $value = $request->input("values.{$field->id}");
                if ($value === null || $value === '') {
                    return response()->json([
                        'success' => false,
                        'message' => "Le champ \"{$field->label}\" est obligatoire.",
                    ], 422);
                }
            }
        }

        $response = CensusResponse::create([
            'census_id' => $censusId,
            'agent_id' => $user->id,
            'geographic_area_id' => $agent->geographic_area_id,
            'respondent_name' => $request->respondent_name,
            'respondent_phone' => $request->respondent_phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        foreach ($census->fields as $field) {
            $value = $request->input("values.{$field->id}");
            if ($value !== null) {
                CensusResponseValue::create([
                    'response_id' => $response->id,
                    'field_id' => $field->id,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }
        }

        $response->load('values.field');

        return response()->json([
            'success' => true,
            'message' => 'Réponse enregistrée avec succès',
            'response' => $response,
        ], 201);
    }

    /**
     * Agent: voir ses propres réponses collectées pour un recensement.
     */
    public function myResponses(Request $request, $censusId)
    {
        $user = $request->user();

        CensusAgent::where('census_id', $censusId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $responses = CensusResponse::with(['values.field', 'geographicArea:id,name'])
            ->where('census_id', $censusId)
            ->where('agent_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['responses' => $responses]);
    }
}
