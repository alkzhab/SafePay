<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des utilisateurs - Admin</title>
    <link rel="stylesheet" href="css/admin_account.css">
</head>

<body>
    
    <!-- Sidebar de navigation Admin -->
    <?php include 'admin_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h1>Liste des utilisateurs</h1>

        <!-- Tableau des utilisateurs -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Login</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Actif</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['id_utilisateur']) ?></td>
                        <td><?= htmlspecialchars($u['login']) ?></td>
                        <td><?= htmlspecialchars($u['nom']) ?></td>
                        <td><?= htmlspecialchars($u['prenom']) ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td class="<?= $u['actif'] ? 'status-active' : 'status-inactive' ?>"><?= $u['actif'] ? 'Oui' : 'Non' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>