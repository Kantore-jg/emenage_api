<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusResponse;
use App\Models\CensusResponseValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CensusStatsController extends Controller
{
    /**
     * Statistiques d'un recensement.
     */
    public function show($censusId)
    {
        $census = Census::with('fields')->findOrFail($censusId);
        $totalResponses = CensusResponse::where('census_id', $censusId)->count();

        $responsesByAgent = CensusResponse::where('census_id', $censusId)
            ->select('agent_id', DB::raw('COUNT(*) as total'))
            ->groupBy('agent_id')
            ->with('agent:id,nom')
            ->get()
            ->map(fn($r) => ['agent' => $r->agent->nom ?? '', 'total' => $r->total]);

        $responsesByZone = CensusResponse::where('census_id', $censusId)
            ->select('geographic_area_id', DB::raw('COUNT(*) as total'))
            ->groupBy('geographic_area_id')
            ->with('geographicArea:id,name')
            ->get()
            ->map(fn($r) => ['zone' => $r->geographicArea->name ?? 'Non défini', 'total' => $r->total]);

        $responsesByDay = CensusResponse::where('census_id', $censusId)
            ->select(DB::raw("DATE(created_at) as jour"), DB::raw('COUNT(*) as total'))
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        // Statistiques par champ
        $fieldStats = [];
        foreach ($census->fields as $field) {
            $stat = ['field_id' => $field->id, 'label' => $field->label, 'type' => $field->type];

            $values = CensusResponseValue::where('field_id', $field->id)
                ->pluck('value')
                ->filter(fn($v) => $v !== null && $v !== '');

            if (in_array($field->type, ['number'])) {
                $numeric = $values->map(fn($v) => (float) $v);
                $stat['count'] = $numeric->count();
                $stat['sum'] = $numeric->sum();
                $stat['avg'] = $numeric->avg();
                $stat['min'] = $numeric->min();
                $stat['max'] = $numeric->max();
            } elseif (in_array($field->type, ['select', 'boolean'])) {
                $stat['distribution'] = $values->countBy()->sortDesc()->toArray();
            } elseif ($field->type === 'multi_select') {
                $all = [];
                foreach ($values as $v) {
                    $decoded = json_decode($v, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $item) {
                            $all[] = $item;
                        }
                    }
                }
                $stat['distribution'] = collect($all)->countBy()->sortDesc()->toArray();
            } else {
                $stat['total_reponses'] = $values->count();
                $stat['vides'] = $totalResponses - $values->count();
            }

            $fieldStats[] = $stat;
        }

        return response()->json([
            'census' => [
                'id' => $census->id,
                'titre' => $census->titre,
                'statut' => $census->statut,
            ],
            'total_responses' => $totalResponses,
            'by_agent' => $responsesByAgent,
            'by_zone' => $responsesByZone,
            'by_day' => $responsesByDay,
            'field_stats' => $fieldStats,
        ]);
    }

    /**
     * Comparer les statistiques entre 2 recensements.
     */
    public function compare(Request $request)
    {
        $request->validate([
            'census_ids' => 'required|array|min:2',
            'census_ids.*' => 'exists:censuses,id',
        ]);

        $results = [];
        foreach ($request->census_ids as $censusId) {
            $census = Census::with('fields')->findOrFail($censusId);
            $totalResponses = CensusResponse::where('census_id', $censusId)->count();

            $byZone = CensusResponse::where('census_id', $censusId)
                ->select('geographic_area_id', DB::raw('COUNT(*) as total'))
                ->groupBy('geographic_area_id')
                ->with('geographicArea:id,name')
                ->get()
                ->map(fn($r) => ['zone' => $r->geographicArea->name ?? 'Non défini', 'total' => $r->total]);

            $fieldSummaries = [];
            foreach ($census->fields as $field) {
                $values = CensusResponseValue::where('field_id', $field->id)
                    ->pluck('value')
                    ->filter(fn($v) => $v !== null && $v !== '');

                $summary = ['label' => $field->label, 'type' => $field->type, 'count' => $values->count()];

                if ($field->type === 'number') {
                    $numeric = $values->map(fn($v) => (float) $v);
                    $summary['avg'] = $numeric->avg();
                    $summary['sum'] = $numeric->sum();
                } elseif (in_array($field->type, ['select', 'boolean'])) {
                    $summary['top_value'] = $values->countBy()->sortDesc()->keys()->first();
                }

                $fieldSummaries[] = $summary;
            }

            $results[] = [
                'census_id' => $census->id,
                'titre' => $census->titre,
                'statut' => $census->statut,
                'date_debut' => $census->date_debut,
                'date_fin' => $census->date_fin,
                'total_responses' => $totalResponses,
                'by_zone' => $byZone,
                'fields' => $fieldSummaries,
            ];
        }

        return response()->json(['comparison' => $results]);
    }
}
