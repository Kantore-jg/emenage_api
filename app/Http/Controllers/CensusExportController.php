<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CensusExportController extends Controller
{
    /**
     * Export CSV des réponses d'un recensement.
     */
    public function exportCsv($censusId)
    {
        $census = Census::with('fields')->findOrFail($censusId);
        $responses = CensusResponse::with(['values.field', 'agent:id,nom', 'geographicArea:id,name'])
            ->where('census_id', $censusId)
            ->orderBy('created_at')
            ->get();

        $headers = ['#', 'Date', 'Agent', 'Zone', 'Nom répondant', 'Téléphone'];
        foreach ($census->fields as $field) {
            $headers[] = $field->label;
        }

        $rows = [];
        foreach ($responses as $index => $response) {
            $row = [
                $index + 1,
                $response->created_at->format('Y-m-d H:i'),
                $response->agent->nom ?? '',
                $response->geographicArea->name ?? '',
                $response->respondent_name ?? '',
                $response->respondent_phone ?? '',
            ];

            foreach ($census->fields as $field) {
                $val = $response->values->firstWhere('field_id', $field->id);
                $decoded = $val?->value;

                if ($decoded && $this->isJson($decoded)) {
                    $decoded = implode(', ', json_decode($decoded, true));
                }

                $row[] = $decoded ?? '';
            }

            $rows[] = $row;
        }

        $filename = 'recensement_' . $census->id . '_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Données tabulaires JSON (pour affichage côté frontend avant export).
     */
    public function table($censusId, Request $request)
    {
        $census = Census::with('fields')->findOrFail($censusId);

        $query = CensusResponse::with(['values.field', 'agent:id,nom', 'geographicArea:id,name'])
            ->where('census_id', $censusId)
            ->orderByDesc('created_at');

        if ($request->geographic_area_id) {
            $query->where('geographic_area_id', $request->geographic_area_id);
        }
        if ($request->agent_id) {
            $query->where('agent_id', $request->agent_id);
        }

        $responses = $query->get();

        $columns = ['Date', 'Agent', 'Zone', 'Répondant'];
        foreach ($census->fields as $field) {
            $columns[] = $field->label;
        }

        $data = $responses->map(function ($response) use ($census) {
            $row = [
                'id' => $response->id,
                'date' => $response->created_at->format('Y-m-d H:i'),
                'agent' => $response->agent->nom ?? '',
                'zone' => $response->geographicArea->name ?? '',
                'respondent' => $response->respondent_name ?? '',
                'fields' => [],
            ];

            foreach ($census->fields as $field) {
                $val = $response->values->firstWhere('field_id', $field->id);
                $decoded = $val?->value;

                if ($decoded && $this->isJson($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $row['fields'][$field->id] = $decoded ?? '';
            }

            return $row;
        });

        return response()->json([
            'columns' => $columns,
            'field_ids' => $census->fields->pluck('id')->toArray(),
            'data' => $data,
            'total' => $responses->count(),
        ]);
    }

    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE && str_starts_with(trim($string), '[');
    }
}
