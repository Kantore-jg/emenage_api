<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IdentityCardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('household');

        if (!$user->photo_profil) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez avoir une photo de profil pour obtenir votre carte d\'identité biométrique.',
            ], 400);
        }

        $qrData = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'tel' => $user->telephone,
            'role' => $user->role,
            'quartier' => $user->household->quartier ?? 'N/A',
            'adresse' => $user->household->adresse ?? 'N/A',
            'date_generation' => now()->toISOString(),
        ]);

        $qrCodeImage = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::format('svg')->size(250)->margin(1)->generate($qrData)
        );

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'telephone' => $user->telephone,
                'role' => $user->role,
                'photo_profil' => $user->photo_profil,
                'quartier' => $user->household->quartier ?? null,
                'adresse' => $user->household->adresse ?? null,
            ],
            'qrCodeImage' => $qrCodeImage,
            'dateEmission' => now()->format('d/m/Y'),
        ]);
    }

    public function qrcode(Request $request)
    {
        $user = $request->user();
        $user->load('household');

        $qrData = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'tel' => $user->telephone,
            'role' => $user->role,
            'quartier' => $user->household->quartier ?? 'N/A',
            'adresse' => $user->household->adresse ?? 'N/A',
            'date_generation' => now()->toISOString(),
        ]);

        $qrCode = QrCode::format('png')->size(300)->margin(2)->generate($qrData);

        return response($qrCode)->header('Content-Type', 'image/png');
    }
}
