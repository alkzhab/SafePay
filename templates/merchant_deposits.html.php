<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commerçant - Mes remises</title>

<link rel="stylesheet" href="css/merchant_deposits.css">
</head>
<body>

    <!-- Sidebar de navigation Commerçant -->
    <?php include 'merchant_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">

        <!-- TITRE -->
        <h2>Mes Remises</h2>
        <p>Total remises : <strong><?= $totalRemises ?></strong></p>

        <!-- FILTRE/RECHERCHE -->
        <div class="search-container">
            <form method="get" class="filter-form">

                <!-- Filtre par numéro de remise -->
                <select name="search" class="filter-select">
                    <option value="">Filtrer par N° remise</option>
                    <?php
                        $stmtNum = $pdo->prepare("SELECT numRemise FROM remise WHERE id_commercant = :id ORDER BY dateRemise DESC");
                        $stmtNum->execute(['id'=>$id_commercant]);
                        $allRemises = $stmtNum->fetchAll(PDO::FETCH_COLUMN);
                        foreach($allRemises as $num){
                            $selected = ($num === $search) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($num)."\" $selected>".htmlspecialchars($num)."</option>";
                        }
                    ?>
                </select>

                <!-- Filtre par dates -->
                <label for="date_start" class="filter-label">
                    Date début :
                    <input type="date" id="date_start" name="date_start" value="<?= htmlspecialchars($_GET['date_start'] ?? '') ?>">
                </label>

                <label for="date_end" class="filter-label">
                    Date fin :
                    <input type="date" id="date_end" name="date_end" value="<?= htmlspecialchars($_GET['date_end'] ?? '') ?>">
                </label>

                <!-- Nombre de remises par page -->
                <input type="number" name="per_page" value="<?= $perPage ?>" min="1" class="filter-perpage" title="Nombre de remises affichées par page">

                <button type="submit" class="btn-filter">Filtrer</button>
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

        <!-- TABLEAUX DE REMISES -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° Remise</th>
                        <th>Date traitement</th>
                        <th>Nombre Transactions</th>
                        <th>Devise</th>
                        <th>Montant total</th>
                        <th>Sens</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($remises as $r): 
                        // Récupération des transactions associées à la remise
                        $stmtDetail = $pdo->prepare("
                            SELECT t.*, c.numCarteClient AS carte_client, c.reseauClient AS reseau_client
                            FROM transaction t
                            LEFT JOIN clientfinal c ON t.id_client = c.id_client
                            WHERE t.id_remise = :id
                            ORDER BY t.dateVente
                        ");
                        $stmtDetail->execute(['id' => $r['id_remise']]);
                        $transactions = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

                        $devise = $transactions[0]['devise'] ?? 'EUR';
                        $totalMontant = array_sum(array_map(fn($t)=>($t['sens']==='+'?1:-1)*$t['montant'],$transactions));
                        $nbTransactions = count($transactions);
                    ?>
                    <!-- Ligne principale de la remise -->
                    <tr class="main-row">
                        <td><?= htmlspecialchars($r['numRemise']) ?></td>
                        <td><?= htmlspecialchars($r['dateRemise']) ?></td>
                        <td><?= $nbTransactions ?></td>
                        <td><?= htmlspecialchars($devise) ?></td>
                        <td><?= number_format($totalMontant,2,',',' ') ?></td>
                        <td><?= $nbTransactions>0 ? htmlspecialchars($transactions[0]['sens']) : '-' ?></td>
                        <td><span class="toggle-details">➕</span></td>
                    </tr>

                    <!-- Ligne détaillée (transactions de la remise) -->
                    <tr class="details-row">
                        <td colspan="7">
                            <div class="details-container">
                                <div class="export-details">
                                    <form method="post">
                                        <input type="hidden" name="id_remise" value="<?= $r['id_remise'] ?>">

                                        <button name="format" value="csv">Exporter CSV</button>
                                        <button name="format" value="xls">Exporter XLS</button>
                                        <button name="format" value="pdf">Exporter PDF</button>
                                    </form>
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>N° Carte</th>
                                            <th>Réseau</th>
                                            <th>N° autorisation</th>
                                            <th>Devise</th>
                                            <th>Montant</th>
                                            <th>Sens</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($transactions as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['carte_client']) ?></td>
                                            <td><?= htmlspecialchars($t['reseau_client']) ?></td>
                                            <td><?= htmlspecialchars($t['numAutorisation']) ?></td>
                                            <td><?= htmlspecialchars($t['devise']) ?></td>
                                            <td class="<?= $t['sens']==='-'?'amount-negative':'' ?>"><?= number_format($t['montant'],2,',',' ') ?></td>
                                            <td><?= htmlspecialchars($t['sens']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p class="details-total">
                                    Total transactions: <?= number_format($totalMontant,2,',',' ') ?> €
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&per_page=<?= $perPage ?>&page=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Scripts -->
    <script src="js/merchant_deposits.js"></script>

</body>
</html>