<?php

namespace App\Http\Controllers;

use App\Models\GeographicArea;
use App\Models\GeographicLevel;
use Illuminate\Http\Request;

class GeographicController extends Controller
{
    /**
     * Tous les niveaux géographiques (province, commune, zone, colline)
     */
    public function levels()
    {
        $levels = GeographicLevel::orderBy('level_order')->get();
        return response()->json(['levels' => $levels]);
    }

    /**
     * Les zones racines (provinces) ou les enfants d'une zone donnée.
     * GET /geographic/areas?parent_id=5
     * GET /geographic/areas (sans parent_id → provinces)
     */
    public function areas(Request $request)
    {
        $query = GeographicArea::with('level:id,name,slug');

        if ($request->has('parent_id') && $request->parent_id !== null) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        $areas = $query->orderBy('name')->get();

        return response()->json(['areas' => $areas]);
    }

    /**
     * Détail d'une zone avec son chemin complet et ses enfants directs.
     */
    public function show($id)
    {
        $area = GeographicArea::with(['level:id,name,slug', 'children.level:id,name,slug'])
            ->findOrFail($id);

        $ancestors = $area->ancestors()->map(function ($a) {
            $a->load('level:id,name,slug');
            return $a;
        });

        return response()->json([
            'area' => $area,
            'ancestors' => $ancestors->values(),
            'full_path' => $area->full_path,
            'children' => $area->children()->orderBy('name')->get(),
        ]);
    }

    /**
     * Arbre complet de la hiérarchie (pour l'admin).
     * Retourne l'arbre avec max 2 niveaux de profondeur pour la performance.
     */
    public function tree(Request $request)
    {
        $maxDepth = $request->input('depth', 2);

        $query = GeographicArea::with('level:id,name,slug')
            ->whereNull('parent_id')
            ->orderBy('name');

        if ($maxDepth >= 1) {
            $query->with(['children' => function ($q) use ($maxDepth) {
                $q->orderBy('name')->with('level:id,name,slug');
                if ($maxDepth >= 2) {
                    $q->with(['children' => function ($q2) use ($maxDepth) {
                        $q2->orderBy('name')->with('level:id,name,slug');
                        if ($maxDepth >= 3) {
                            $q2->with(['children' => function ($q3) {
                                $q3->orderBy('name')->with('level:id,name,slug');
                            }]);
                        }
                    }]);
                }
            }]);
        }

        return response()->json(['tree' => $query->get()]);
    }

    /**
     * Recherche dans les zones géographiques.
     * GET /geographic/search?q=buyenzi
     */
    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);

        $areas = GeographicArea::with('level:id,name,slug')
            ->where('name', 'LIKE', '%' . $request->q . '%')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'level' => $area->level->name,
                    'full_path' => $area->full_path,
                ];
            });

        return response()->json(['results' => $areas]);
    }
}
