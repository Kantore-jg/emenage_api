<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe reinitialise</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Bonjour {{ $user->nom }},</h2>

    <p>Votre mot de passe e-Menage vient d'etre reinitialise.</p>

    <p>
        <strong>Nouveau mot de passe :</strong> {{ $plainPassword }}
    </p>

    <p>Connectez-vous avec ce mot de passe puis modifiez-le des que possible pour securiser votre compte.</p>
</body>
</html>
