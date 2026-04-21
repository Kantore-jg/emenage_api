<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $announcement->titre }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>{{ $announcement->titre }}</h2>

    <p>
        <strong>Autorite :</strong> {{ $announcement->autorite }}<br>
        <strong>Date :</strong> {{ optional($announcement->date)->format('d/m/Y') ?? $announcement->date }}
        @if($authorName)
            <br><strong>Auteur :</strong> {{ $authorName }}
        @endif
    </p>

    <div style="white-space: pre-line;">{{ $announcement->contenu }}</div>

    <p style="margin-top: 24px;">Ce message vous a ete envoye par la plateforme e-Menage.</p>
</body>
</html>
