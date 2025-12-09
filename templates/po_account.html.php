!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande gestion commerçant – PO</title>
    <link rel="stylesheet" href="css/po_account.css">
</head>

<body>

    <!-- Sidebar de navigation PO -->
    <?php include 'po_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Envoyer une demande à l'administrateur</h2>

        <?php if($erreur): ?>
            <p style="color:red"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <?php if($success): ?>
            <p style="color:green"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <!-- Formulaire de demande -->
        <form method="post" id="requeteForm">
            <label for="type_requete">Type de requête</label>
            <select name="type_requete" id="type_requete" required>
                <option value="">-- Sélectionnez --</option>
                <option value="ajouter_commercant">Ajouter un commerçant</option>
                <option value="supprimer_commercant">Supprimer un commerçant</option>
                <option value="modifier_commercant">Modifier un commerçant</option>
                <option value="activer_compte">Activer un compte</option>
                <option value="desactiver_compte">Désactiver un compte</option>
            </select>
            <div id="dynamicFields"></div>
            <button type="submit">Envoyer la requête</button>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        const commercants = <?= json_encode($commercants) ?>;
    </script>

    <script src="js/po_account.js"></script>

</body>
</html>