<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\UserEmailService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('author:id,nom')
            ->orderByDesc('created_at');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', "%{$search}%")
                  ->orWhere('contenu', 'LIKE', "%{$search}%")
                  ->orWhere('autorite', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $paginated = $query->paginate($perPage)->through(function ($a) {
            return [
                'id' => $a->id,
                'titre' => $a->titre,
                'contenu' => $a->contenu,
                'autorite' => $a->autorite,
                'date' => $a->date,
                'created_at' => $a->created_at,
                'author_id' => $a->author_id,
                'author_name' => $a->author->nom ?? '',
            ];
        });

        return response()->json([
            'announcements' => $paginated->items(),
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

    public function show($id)
    {
        $announcement = Announcement::with('author:id,nom,role')->findOrFail($id);

        return response()->json([
            'announcement' => [
                'id' => $announcement->id,
                'titre' => $announcement->titre,
                'contenu' => $announcement->contenu,
                'autorite' => $announcement->autorite,
                'date' => $announcement->date,
                'created_at' => $announcement->created_at,
                'author_id' => $announcement->author_id,
                'author_name' => $announcement->author->nom ?? '',
                'author_role' => $announcement->author->role ?? '',
            ],
        ]);
    }

    public function store(Request $request, UserEmailService $userEmailService)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'autorite' => 'required|string|max:255',
            'date' => 'nullable|date',
            'send_email_to_all' => 'nullable|boolean',
        ]);

        $announcement = Announcement::create([
            'author_id' => $request->user()->id,
            'titre' => trim($request->titre),
            'contenu' => trim($request->contenu),
            'autorite' => trim($request->autorite),
            'date' => $request->date ?? now()->toDateString(),
        ]);

        $emailsSent = 0;
        if ($request->boolean('send_email_to_all')) {
            $emailsSent = $userEmailService->sendAnnouncementToAllUsers($announcement, $request->user());
        }

        return response()->json([
            'success' => true,
            'message' => 'Communiqué publié avec succès',
            'emails_sent' => $emailsSent,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = $request->user();

        if ($announcement->author_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier ce communiqué',
            ], 403);
        }

        $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'autorite' => 'required|string|max:255',
            'date' => 'nullable|date',
        ]);

        $announcement->update([
            'titre' => trim($request->titre),
            'contenu' => trim($request->contenu),
            'autorite' => trim($request->autorite),
            'date' => $request->date ?? $announcement->date,
        ]);

        return response()->json(['success' => true, 'message' => 'Communiqué modifié avec succès']);
    }

    public function destroy(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = $request->user();

        if ($announcement->author_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer ce communiqué',
            ], 403);
        }

        $announcement->delete();

        return response()->json(['success' => true, 'message' => 'Communiqué supprimé avec succès']);
    }
}
