<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bienvenue sur e-Menage</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Bienvenue {{ $user->nom }},</h2>

    <p>Votre compte e-Menage a ete cree avec succes.</p>

    <p>
        <strong>Telephone :</strong> {{ $user->telephone }}<br>
        @if($user->email)
            <strong>Email :</strong> {{ $user->email }}<br>
        @endif
        <strong>Role :</strong> {{ $user->role }}<br>
        <strong>Mot de passe temporaire :</strong> {{ $plainPassword }}
    </p>

    @if($createdByName)
        <p>Ce compte a ete enregistre par {{ $createdByName }}.</p>
    @endif

    <p>Nous vous recommandons de changer ce mot de passe apres votre premiere connexion.</p>
    <p>
        Visiter la plateforme :
        <a href="http://34.59.117.62:82">http://34.59.117.62:82</a>
    </p>
    <p>Merci de faire confiance a la plateforme e-Menage.</p>
</body>
</html>
