<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CensusAgentController extends Controller
{
    /**
     * Lister les agents d'un recensement.
     */
    public function index($censusId)
    {
        $census = Census::findOrFail($censusId);

        $agents = CensusAgent::with([
            'user:id,nom,telephone,email',
            'geographicArea:id,name',
        ])
            ->where('census_id', $censusId)
            ->get();

        return response()->json(['agents' => $agents]);
    }

    /**
     * Créer un agent et l'assigner à un recensement.
     * L'admin crée l'utilisateur agent_recensement + l'assigne à la campagne avec une zone.
     */
    public function store(Request $request, $censusId)
    {
        $census = Census::findOrFail($censusId);

        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|unique:users',
            'geographic_area_id' => 'required|exists:geographic_areas,id',
        ]);

        $password = Str::random(8);

        $user = User::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => $password,
            'role' => 'agent_recensement',
            'created_by' => $request->user()->id,
            'geographic_area_id' => $request->geographic_area_id,
        ]);

        $agent = CensusAgent::create([
            'census_id' => $census->id,
            'user_id' => $user->id,
            'geographic_area_id' => $request->geographic_area_id,
        ]);

        $agent->load(['user:id,nom,telephone,email', 'geographicArea:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Agent créé et assigné avec succès',
            'agent' => $agent,
            'password' => $password,
        ], 201);
    }

    /**
     * Assigner un utilisateur existant comme agent d'un recensement.
     */
    public function assign(Request $request, $censusId)
    {
        $census = Census::findOrFail($censusId);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'geographic_area_id' => 'required|exists:geographic_areas,id',
        ]);

        $exists = CensusAgent::where('census_id', $censusId)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà assigné à ce recensement.',
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        if ($user->role !== 'agent_recensement') {
            $user->update(['role' => 'agent_recensement']);
        }

        $agent = CensusAgent::create([
            'census_id' => $census->id,
            'user_id' => $request->user_id,
            'geographic_area_id' => $request->geographic_area_id,
        ]);

        $agent->load(['user:id,nom,telephone,email', 'geographicArea:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Agent assigné avec succès',
            'agent' => $agent,
        ], 201);
    }

    /**
     * Retirer un agent d'un recensement.
     */
    public function destroy($censusId, $agentId)
    {
        $agent = CensusAgent::where('census_id', $censusId)
            ->where('id', $agentId)
            ->firstOrFail();

        $agent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Agent retiré du recensement',
        ]);
    }
}
