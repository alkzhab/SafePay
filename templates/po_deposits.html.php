<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Owner - Remises</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/po_deposits.css">
</head>

<body>

    <!-- Sidebar de navigation PO -->
    <?php include 'po_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Remises - Product Owner</h2>

        <!-- Filters -->
        <form class="filters" method="get">
            <select name="siren">
                <option value="">Tous les SIREN</option>
                <?php foreach($commercants as $c): ?>
                    <option value="<?= $c['siren'] ?>" <?= $selectedSiren==$c['siren']?'selected':'' ?>><?= htmlspecialchars($c['siren']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="raison">
                <option value="">Toutes les raisons sociales</option>
                <?php foreach($commercants as $c): ?>
                    <option value="<?= htmlspecialchars($c['raison_sociale']) ?>" <?= $selectedRaison==$c['raison_sociale']?'selected':'' ?>><?= htmlspecialchars($c['raison_sociale']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" placeholder="Date début">
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" placeholder="Date fin">
            <button type="submit">Filtrer</button>
        </form>
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

        <!-- Table Remises -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° SIREN</th>
                        <th>Raison sociale</th>
                        <th>N° Remise</th>
                        <th>Date traitement</th>
                        <th>Nbre transactions</th>
                        <th>Devise</th>
                        <th>Montant total</th>
                        <th>Sens</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($remises as $r): 
                        $transactions = $transactionsByRemise[$r['id_remise']] ?? [];
                        $montant = $r['montant_total'] ?? 0;
                    ?>
                        <tr class="main-row">
                            <td><?= htmlspecialchars($r['id_commercant']) ?></td>
                            <td><?= htmlspecialchars($r['raison_sociale']) ?></td>
                            <td><?= htmlspecialchars($r['numRemise']) ?></td>
                            <td><?= htmlspecialchars($r['dateRemise']) ?></td>
                            <td><?= $r['nb_transactions'] ?></td>
                            <td>EUR</td>
                            <td class="<?= $r['montant_total']<0?'amount-negative':'' ?>"><?= number_format($r['montant_total'],2,',',' ') ?></td>
                            <td><?= $r['montant_total'] >= 0 ? '+' : '-' ?></td>
                            <td><span class="toggle-details">➕</span></td>
                        </tr>
                        <tr class="details-row">
                            <td colspan="7">
                                <div class="details">
                                    <div class="export-details">
                                        <form method="post">
                                            <input type="hidden" name="id_remise" value="<?= $r['id_remise'] ?>">

                                            <button name="format" value="csv">Exporter CSV</button>
                                            <button name="format" value="xls">Exporter XLS</button>
                                            <button name="format" value="pdf">Exporter PDF</button>
                                        </form>
                                    </div>
                                    <table style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th>Date vente</th>
                                                <th>N° Carte</th>
                                                <th>Réseau</th>
                                                <th>N° Autorisation</th>
                                                <th>Devise</th>
                                                <th>Montant</th>
                                                <th>Sens</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($transactions)): ?>
                                                <tr><td colspan="7">Aucune transaction</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($transactions as $t): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($t['dateVente']) ?></td>
                                                    <td><?= htmlspecialchars($t['numCarteClient']) ?></td>
                                                    <td><?= htmlspecialchars($t['reseauClient']) ?></td>
                                                    <td><?= htmlspecialchars($t['numAutorisation']) ?></td>
                                                    <td><?= htmlspecialchars($t['devise']) ?></td>
                                                    <td class="<?= $t['sens'] === '-' ? 'amount-negative' : '' ?>"> <?= number_format($t['montant'],2,',',' ') ?></td>
                                                    <td><?= htmlspecialchars($t['sens']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/po_deposits.js"></script>

</body>
</html>