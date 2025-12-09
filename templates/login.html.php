<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SafePay – Connexion</title>

<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

    <!-- Carte de connexion -->
    <div class="login-card">

        <div class="logo-container">
            <img src="img/logo.png" alt="Logo SafePay">
        </div>

        <h2>Connexion</h2>

        <form method="post">

            <?php if (!empty($erreur)): ?>
                <div class="error-message"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <!-- IDENTIFIANT -->
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="login" placeholder="Nom d'utilisateur" required>
            </div>

            <!-- MOT DE PASSE -->
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Mot de passe" required>
            </div>

            <div class="forgot">
                <a href="#">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>

        </form>
    </div>

</body>
</html>
