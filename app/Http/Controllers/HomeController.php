<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class HomeController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('author:id,nom')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'contenu' => $a->contenu,
                    'autorite' => $a->autorite,
                    'date' => $a->date,
                    'author_name' => $a->author->nom ?? '',
                ];
            });

        return response()->json(['announcements' => $announcements]);
    }
}
