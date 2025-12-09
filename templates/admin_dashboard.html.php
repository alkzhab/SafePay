<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>

<body>

    <!-- Sidebar de navigation Admin -->
    <?php include 'admin_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">

        <!-- Requêtes du Product Owner -->
        <div class="requetes">
            <h2>Requêtes reçues</h2>
            <?php foreach($requetes as $r): ?>
                <?php 
                    $details = json_decode($r['details'], true);
                    $user = $usersCache[$details['id_utilisateur']] ?? null;
                ?>
                <div class="requete-item">
                    <strong><?= htmlspecialchars(ucfirst(str_replace('_',' ',$r['type']))) ?></strong>
                    <small>(<?= $r['date_requete'] ?>)</small>
                    

                    <?php if ($user): ?>
                        <div class="user-box">
                            <h4>Compte concerné</h4>
                                Nom : <?= htmlspecialchars($user['prenom']." ".$user['nom']) ?><br>
                                Mail : <?= htmlspecialchars($user['email']) ?><br>
                                Rôle : <?= htmlspecialchars($user['role']) ?><br>
                                Raison sociale : <?= htmlspecialchars($user['raison_sociale'] ?? 'Aucun commerce') ?><br>
                                N° Siren : <?= htmlspecialchars($user['siren'] ?? '-') ?><br>
                                Statut : <?= $user['actif'] ? 'Actif' : 'Inactif' ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulaire validation -->
                    <form method="post" class="form-valide">
                        <input type="hidden" name="validate_id" value="<?= $r['id_requete'] ?>">
                        <button type="submit" class="btn">
                            Valider
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Actions Admin -->
        <div class="actions">
            <h2>Action Admin</h2>
            <?php if($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>

            <label>Type d'action</label>
            <select id="actionSelect">
                <option value="">-- Choisir --</option>
                <option value="ajouter">Ajouter</option>
                <option value="supprimer">Supprimer</option>
                <option value="modifier">Modifier</option>
                <option value="activer">Activer un compte</option>
                <option value="desactiver">Désactiver un compte</option>
            </select>

            <form method="POST" id="adminForm" class="form-admin">
                <input type="hidden" name="action_type" id="action_type">
                <div id="dynamicFields"></div>
                    <button type="submit">Valider</button>
            </form>
        </div>
    </div>

    <!-- Scriptes -->
    <script>
        const commercants = <?= json_encode($commercants) ?>;
    </script>

    <script src="js/admin_dashboard.js"></script>

</body>
</html>