<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commerçant - Tableau de bord</title>
<link rel="stylesheet" href="css/merchant_dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- Sidebar de navigation Commerçant -->
    <?php include 'merchant_sidebar.html.php'; ?>

    <!-- Contenu principal -->
    <div class="main">
        <h2>Bienvenue, <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></h2>

        <!-- GRAPHIQUE -->
        <div class="card-container">
            <div class="card <?= $currentBalance < 0 ? 'negative' : 'positive' ?>">
                <div>Solde actuel</div>
                <div class="amount"><?= number_format($currentBalance,2,',',' ') ?> €</div>
            </div>
        </div>

        <div class="graph-container">
            <button class="btn-export" onclick="exportBalanceChart()">Télécharger le graphique</button>
            <h3>Évolution de la trésorerie</h3>
            <canvas id="balanceChart"></canvas>
        </div>
    </div>

    <!-- Scriptes -->
    <script>
        const labelsFromPHP = <?= json_encode($labels) ?>;
        const dataFromPHP = <?= json_encode($balanceGraph) ?>;
    </script>
    <script src="js/merchant_dashboard.js"></script>

</body>
</html>
