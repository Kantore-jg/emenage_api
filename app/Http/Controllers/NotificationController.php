<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id);

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('lu')) {
            $query->where('lu', $request->boolean('lu'));
        }

        $perPage = $request->input('per_page', 20);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'notifications' => $paginated->items(),
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

    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée ou non autorisée',
            ], 404);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification supprimée']);
    }
}
