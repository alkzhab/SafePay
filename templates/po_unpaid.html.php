<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Owner - Impayés</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/po_unpaid.css">
</head>

<body>

    <!-- Sidebar de navigation PO -->
    <?php include 'po_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Recherche des Impayés</h2>
        <p>Total impayés : <strong><?= number_format($totalImpayes,2,',',' ') ?> €</strong></p>

        <div class="filter-chart-wrapper">

            <!-- COLONNE GAUCHE : FILTRES -->
            <div class="filter-column">
                <h3>Filtres</h3>

                <form class="filters" method="get">

                    <select name="siren">
                        <option value="">Tous les SIREN</option>
                        <?php foreach($commercants as $c): ?>
                            <option value="<?= $c['siren'] ?>" <?= $searchSiren==$c['siren']?'selected':'' ?>>
                                <?= htmlspecialchars($c['siren']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="raison">
                        <option value="">Toutes les raisons sociales</option>
                        <?php foreach($commercants as $c): ?>
                            <option value="<?= htmlspecialchars($c['raison_sociale']) ?>" 
                                <?= $searchRaison==$c['raison_sociale']?'selected':'' ?>>
                                <?= htmlspecialchars($c['raison_sociale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">

                    <select name="num_dossier">
                        <option value="">Tous les numéros d’impayés</option>
                        <?php foreach($listeImpayes as $imp): ?>
                            <option value="<?= htmlspecialchars($imp['numImpayé']) ?>"
                                <?= $searchNumDossier == $imp['numImpayé'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($imp['numImpayé']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Filtrer</button>
                </form>
            </div>

            <!-- COLONNE DROITE : GRAPHIQUE -->
            <div class="chart-column">
                <div class="card">
                    <button class="btn-export" onclick="exportChart()">Télécharger le graphique</button>
                    <h3 style="margin-bottom:15px;">Répartition des impayés par motif</h3>
                    <canvas id="motifChart"></canvas>
                </div>
            </div>
        </div>
        <div class="export-dropdown">
            <button class="dropdown-toggle" title="Exporter">Exporter ⋮</button>
            <div class="dropdown-menu">
                <form method="post" action="">
                    <input type="hidden" name="siren" value="<?= htmlspecialchars($searchSiren) ?>">
                    <input type="hidden" name="raison" value="<?= htmlspecialchars($searchRaison) ?>">
                    <input type="hidden" name="date_valeur" value="<?= htmlspecialchars($dateValeur) ?>">
                    <input type="hidden" name="chart_image" id="chart_image">

                    <button type="submit" name="format" value="csv">CSV</button>
                    <button type="submit" name="format" value="xls">XLS</button>
                    <button type="submit" name="format" value="pdf">PDF</button>
                </form>
            </div>
        </div>

        <!-- TABLEAU DES IMPAYÉS -->
        <table>
            <thead>
                <tr>
                    <th>N° SIREN</th>
                    <th>Raison sociale</th>
                    <th>Date vente</th>
                    <th>Date remise</th>
                    <th>N° Carte</th>
                    <th>Réseau</th>
                    <th>N° dossier impayé</th>
                    <th>Devise</th>
                    <th>Montant</th>
                    <th>Libellé impayé</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($impayes as $i): 
                    $montantClass = 'amount-negative';
                    $maskedCarte = $i['numCarteClient'] ? '************'.substr($i['numCarteClient'],-4) : '';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($i['siren']) ?></td>
                        <td><?= htmlspecialchars($i['raison_sociale']) ?></td>
                        <td><?= htmlspecialchars($i['dateVente']) ?></td>
                        <td><?= htmlspecialchars($i['dateRemise']) ?></td>
                        <td><?= $maskedCarte ?></td>
                        <td><?= htmlspecialchars($i['reseau']) ?></td>
                        <td><?= htmlspecialchars($i['numImpayé']) ?></td>
                        <td><?= htmlspecialchars($i['devise']) ?></td>
                        <td class="<?= $montantClass ?>"><?= number_format($i['montant'],2,',',' ') ?></td>
                        <td><?= htmlspecialchars($i['libelle_impaye']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Scripts -->
    <script>
        // Données pour le graphique
        const motifLabels = <?= json_encode(array_column($motifs, 'libelle')) ?>;
        const motifData = <?= json_encode(array_map(fn($m)=>(float)$m['total'], $motifs)) ?>;
    </script>

    <script src="js/po_unpaid.js"></script>

</body>
</html>
