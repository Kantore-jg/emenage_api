<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Report;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, $reportId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($reportId);

        if ($report->citizen_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez noter que vos propres signalements.',
            ], 403);
        }

        if ($report->statut !== 'resolu') {
            return response()->json([
                'success' => false,
                'message' => 'Le signalement doit être résolu avant de donner un avis.',
            ], 422);
        }

        if ($report->feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Un avis a déjà été donné pour ce signalement.',
            ], 409);
        }

        Feedback::create([
            'report_id' => $report->id,
            'citizen_id' => $request->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Merci pour votre retour !',
        ], 201);
    }

    public function myFeedbacks(Request $request)
    {
        $feedbacks = Feedback::where('citizen_id', $request->user()->id)
            ->with('report:id,description,statut')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['feedbacks' => $feedbacks]);
    }
}
