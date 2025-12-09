<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Owner - Tableau de bord</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/po_dashboard.css">
</head>

<body>

    <!-- Sidebar de navigation PO -->
    <?php include 'po_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Tableau de bord - Product Owner</h2>

        <!-- Filtres et export -->
        <div class="search-container">
            <form method="get" class="filters">
                <select name="siren">
                    <option value="">-- Sélectionnez un SIREN --</option>
                    <?php foreach($sirenOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= $option === $searchSiren ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="raison">
                    <option value="">-- Sélectionnez une raison sociale --</option>
                    <?php foreach($raisonOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= $option === $searchRaison ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_valeur" value="<?= htmlspecialchars($dateValeur) ?>">
                <button type="submit">Filtrer</button>
            </form>
        </div>
        <div class="export-dropdown">
            <button class="dropdown-toggle" title="Exporter">Exporter ⋮</button>
            <div class="dropdown-menu">
                <form method="post" action="">
                    <input type="hidden" name="siren" value="<?= htmlspecialchars($searchSiren) ?>">
                    <input type="hidden" name="raison" value="<?= htmlspecialchars($searchRaison) ?>">
                    <input type="hidden" name="date_valeur" value="<?= htmlspecialchars($dateValeur) ?>">

                    <button type="submit" name="format" value="csv">CSV</button>
                    <button type="submit" name="format" value="xls">XLS</button>
                    <button type="submit" name="format" value="pdf">PDF</button>
                </form>
            </div>
        </div>

        <!-- Tableau des annonces -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° de SIREN</th>
                        <th>Raison sociale</th>
                        <th>Nombre Transactions</th>
                        <th>Devise</th>
                        <th>Montant total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($annonces as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['siren']) ?></td>
                            <td><?= htmlspecialchars($a['raison_sociale']) ?></td>
                            <td><?= $a['nb_transactions'] ?></td>
                            <td><?= htmlspecialchars($a['devise']) ?></td>
                            <td class="<?= $a['montant_total']<0 ? 'amount-negative' : '' ?>"><?= number_format($a['montant_total'],2,',',' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/po_dashboard.js"></script>

</body>
</html>
