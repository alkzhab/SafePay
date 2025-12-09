<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commerçant - Impayés</title>

<link rel="stylesheet" href="css/merchant_unpaid.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <!-- Sidebar de navigation Commerçant -->
    <?php include 'merchant_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Dashboard Impayés</h2>

        <!-- KPI -->
        <div class="kpi-row">
            <div class="kpi-card">
                <h3><?= number_format($impayesTotal,2,',',' ') ?> €</h3>
                <p>Total Impayés</p>
            </div>
            <div class="kpi-card">
                <h3><?= number_format($caTotal,2,',',' ') ?> €</h3>
                <p>Chiffre d'affaires</p>
            </div>
            <div class="kpi-card">
                <h3><?= count($impayes) ?></h3>
                <p>Nombre d'impayés</p>
            </div>
        </div>

        <!-- FILTRES -->
        <form class="filters" method="get" style="gap:10px; flex-wrap:wrap;">
            <!-- Filtrer par N° impayé -->
            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                N° Impayé :
                <select name="num_impaye" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db;">
                    <option value="">Tous</option>
                    <?php
                    // Récupérer tous les numéros d'impayé du commerçant
                    $stmtNum = $pdo->prepare("SELECT DISTINCT numImpayé FROM impaye i 
                                            JOIN transaction t ON i.id_transaction = t.id_transaction 
                                            WHERE t.id_commercant=:siren ORDER BY numImpayé DESC");
                    $stmtNum->execute(['siren'=>$siren_commercant]);
                    $allNumImpaye = $stmtNum->fetchAll(PDO::FETCH_COLUMN);
                    foreach($allNumImpaye as $num){
                        $selected = ($num==($_GET['num_impaye']??'')) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($num)."' $selected>".htmlspecialchars($num)."</option>";
                    }
                    ?>
                </select>
            </label>

            <!-- Filtre par dates -->
            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                Date début :
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db;">
            </label>

            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                Date fin :
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db;">
            </label>

            <!-- Filtre par motif -->
            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                Motif :
                <select name="motif" style="padding:8px 10px; border-radius:8px; border:1px solid #d1d5db;">
                    <option value="">Tous les motifs</option>
                    <?php foreach($motifs as $m): ?>
                        <option value="<?= $m['id_motif'] ?>" <?= ($selectedMotif==$m['id_motif'])?'selected':'' ?>>
                            <?= htmlspecialchars($m['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                Trier par montant :
                <select name="sort_montant" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db;">
                    <option value="">Aucun</option>
                    <option value="asc" <?= (($_GET['sort_montant'] ?? '')=='asc')?'selected':'' ?>>Croissant</option>
                    <option value="desc" <?= (($_GET['sort_montant'] ?? '')=='desc')?'selected':'' ?>>Décroissant</option>
                </select>
            </label>


            <label style="display:flex; flex-direction:column; font-size:0.9rem;">
                Impayés par page :
                <input type="number" name="per_page" value="<?= htmlspecialchars($_GET['per_page'] ?? 10) ?>" min="1" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db; width:60px;">
            </label>

            <button type="submit" style="padding:10px 20px; background:#3b82f6; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:500;">Filtrer</button>
        </form>

        <!-- GRAPHIQUES ET TABLEAU -->
        <div class="content-row">
            <div class="chart-column card">
                <button class="btn-export" onclick="exportChart()">Télécharger le graphique</button>
                <h4>Répartition par motif</h4>
                <canvas id="motifChart"></canvas>
            </div>
            <div class="chart-column card">
                <button class="btn-export" onclick="exportChart2()">Télécharger le graphique</button>
                <h4>Évolution impayés / CA</h4>
                <label style="display:flex; flex-direction:column; font-size:0.9rem; width:130px">
                    Type de graphique :
                    <select id="graphTypeSelect" style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db;">
                        <option value="line" <?= ($typeGraph=='line')?'selected':'' ?>>Courbe</option>
                        <option value="bar" <?= ($typeGraph=='bar')?'selected':'' ?>>Barres</option>
                    </select>
                </label>

                <canvas id="impayesChart"></canvas>
            </div>

            <div class="table-column card">
                <div class="export-dropdown">
                    <button class="dropdown-toggle" title="Exporter">Exporter ⋮</button>
                    <div class="dropdown-menu">
                        <form method="post" action="">
                            <input type="hidden" name="siren" value="<?= htmlspecialchars($searchSiren) ?>">
                            <input type="hidden" name="raison" value="<?= htmlspecialchars($searchRaison) ?>">
                            <input type="hidden" name="date_valeur" value="<?= htmlspecialchars($dateValeur) ?>">
                            <input type="hidden" id="chart_image1" name="chart_image1" value="">
                            <input type="hidden" id="chart_image2" name="chart_image2" value="">

                            <button type="submit" name="format" value="csv">CSV</button>
                            <button type="submit" name="format" value="xls">XLS</button>
                            <button type="submit" name="format" value="pdf">PDF</button>
                        </form>
                    </div>
                </div>
                <h4>Liste des Impayés</h4>
                <div style="overflow-x:auto;">
                <table>
                <thead>
                    <tr>
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
                    $colorClass = '';
                    if($i['montant']>=200) $colorClass='amount-high';
                    elseif($i['montant']>=100) $colorClass='amount-medium';
                    elseif($i['montant']<100) $colorClass='amount-low';
                    $maskedCarte = $i['num_carte'] ? '************'.substr($i['num_carte'],-4) : '';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($i['date_vente']) ?></td>
                        <td><?= htmlspecialchars($i['date_remise']) ?></td>
                        <td><?= $maskedCarte ?></td>
                        <td><?= htmlspecialchars($i['reseau']) ?></td>
                        <td><?= htmlspecialchars($i['num_dossier']) ?></td>
                        <td><?= htmlspecialchars($i['devise']) ?></td>
                        <td class="<?= $colorClass ?>"><?= number_format($i['montant'],2,',',' ') ?></td>
                        <td><?= htmlspecialchars($i['libelle_impaye']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                <div class="pagination" style="margin-top:15px;">
                    <?php
                    for($p=1; $p<=$totalPages; $p++){
                        $active = ($p == $page) ? 'active' : '';
                        $query = http_build_query(array_merge($_GET, ['page'=>$p]));
                        echo "<a href='?{$query}' class='{$active}'>{$p}</a>";
                    }
                    ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    // Répartition par motif
    const motifLabels = <?= json_encode(array_column($motifs,'libelle')) ?>;
    const motifData = <?= json_encode(array_map(fn($m)=>(float)$m['total'],$motifs)) ?>;
    const motifColors = ['#ef4444','#f59e0b','#3b82f6','#10b981','#8b5cf6','#f472b6','#facc15','#0ea5e9'];

    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartImpayes = <?= json_encode($chartImpayes) ?>;
    const chartCA = <?= json_encode($chartCA) ?>;
    const chartType = <?= json_encode($typeGraph) ?>;
    </script>

    <script src="js/merchant_unpaid.js"></script>

</body>
</html>