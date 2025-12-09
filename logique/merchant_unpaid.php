<?php
/**
 * merchant_unpaid.php
 *
 * Description:
 * Gestion de la page des impayés du commerçant
 *
 * - Vérifie la session et le rôle COMMERCANT
 * - Récupère le commerçant connecté
 * - Récupère les impayés filtrés par date, motif, numéro d'impayé
 * - Gère le tri par montant
 * - Prépare les données pour graphiques
 * - Gère l'export CSV, XLS et PDF
 *
 * 
 */

// ----------------------
// 1. Vérification de session et rôle
// ----------------------

session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_login'])){
    header('Location:index.php');
    exit();
}

// ----------------------
// 2. Récupération du commerçant
// ----------------------

$login = $_SESSION['user_login'];

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login LIMIT 1");
$stmt->execute(['login'=>$login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user || $user['role'] !== 'COMMERCANT'){
    header('Location:index.php');
    exit();
}

// Récupérer le commerçant
$stmt = $pdo->prepare("SELECT * FROM commercant WHERE id_utilisateur = :id");
$stmt->execute(['id'=>$user['id_utilisateur']]);
$commercant = $stmt->fetch(PDO::FETCH_ASSOC);
$siren_commercant = $commercant['siren'] ?? '';

// ----------------------
// 3. Gestion des filtres GET (dates, motif, type de graphique, pagination)
// ----------------------

// Filtre date, motif et type de graphique
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$selectedMotif = $_GET['motif'] ?? '';
$typeGraph = $_GET['graph_type'] ?? 'line'; 
$periodeGlissante = $_GET['periode'] ?? ''; 

// Nombre d'impayés par page et page actuelle
$perPage = max(1, (int)($_GET['per_page'] ?? 10));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// ----------------------
// 4. Requête principale pour récupérer les impayés
// ----------------------

// Récupérer les impayés avec toutes les infos
$sql = "SELECT 
            t.dateVente AS date_vente,
            r.dateRemise AS date_remise,
            c.numCarteClient AS num_carte,
            t.reseau,
            i.numImpayé AS num_dossier,
            t.devise,
            t.montant,
            m.libelle AS libelle_impaye
        FROM impaye i
        JOIN transaction t ON i.id_transaction = t.id_transaction
        LEFT JOIN remise r ON t.id_remise = r.id_remise
        LEFT JOIN clientfinal c ON t.id_client = c.id_client
        LEFT JOIN motif m ON i.id_motif = m.id_motif
        WHERE t.id_commercant = :siren";

// Ajout des filtres
$params = ['siren' => $siren_commercant];

if($startDate){
    $sql .= " AND t.dateVente >= :start";
    $params['start'] = $startDate;
}
if($endDate){
    $sql .= " AND t.dateVente <= :end";
    $params['end'] = $endDate;
}
if($selectedMotif){
    $sql .= " AND i.id_motif = :motif";
    $params['motif'] = $selectedMotif;
}

if(!empty($_GET['num_impaye'])){
    $sql .= " AND i.numImpayé = :num_impaye";
    $params['num_impaye'] = $_GET['num_impaye'];
}

// Gestion du tri par montant
$sortMontant = $_GET['sort_montant'] ?? '';

$orderBy = "t.dateVente DESC";
if($sortMontant === 'asc'){
    $orderBy = "t.montant ASC";
} elseif($sortMontant === 'desc'){
    $orderBy = "t.montant DESC";
}

$sql .= " ORDER BY $orderBy LIMIT :offset, :per_page";

// Préparation et exécution de la requête
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':siren', $siren_commercant, PDO::PARAM_STR);
if($startDate) $stmt->bindValue(':start', $startDate);
if($endDate) $stmt->bindValue(':end', $endDate);
if($selectedMotif) $stmt->bindValue(':motif', $selectedMotif, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
if (!empty($_GET['num_impaye'])) {
    $stmt->bindValue(':num_impaye', $_GET['num_impaye'], PDO::PARAM_STR);
}
$stmt->execute();
$impayes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ----------------------
// 5. Calcul des KPI et préparation des données pour les graphiques
// ----------------------

// KPI et chart : total impayés et répartition par motif
$impayesTotal = array_sum(array_column($impayes,'montant'));

// Récupérer tous les motifs et leur total
$sqlMotif = "SELECT m.id_motif, m.libelle, SUM(t.montant) as total
             FROM impaye i
             JOIN transaction t ON i.id_transaction = t.id_transaction
             JOIN motif m ON i.id_motif = m.id_motif
             WHERE t.id_commercant=:siren
             GROUP BY m.id_motif";
$stmt = $pdo->prepare($sqlMotif);
$stmt->execute(['siren'=>$siren_commercant]);
$motifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total CA global
$sqlCA = "SELECT SUM(montant) as ca_total FROM transaction WHERE id_commercant=:siren";
$stmt = $pdo->prepare($sqlCA);
$stmt->execute(['siren'=>$siren_commercant]);
$caTotal = (float)$stmt->fetchColumn();

// Total impayés pour pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM impaye i
    JOIN transaction t ON i.id_transaction = t.id_transaction
    WHERE t.id_commercant = :siren");
$totalStmt->execute(['siren'=>$siren_commercant]);
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Données pour le graphique des impayés et chiffre d'affaires par mois
$months = [];
$dataImpayes = [];
$dataCA = [];

// Calcul des mois à afficher
$today = new DateTime();

if($periodeGlissante == '4' || $periodeGlissante == '12'){
    $monthsCount = ($periodeGlissante == '4') ? 4 : 12;
    for($i = $monthsCount-1; $i >= 0; $i--){
        $month = (clone $today)->modify("-$i months")->format('Y-m');
        $months[$month] = date('m/Y', strtotime($month . '-01'));
        $dataImpayes[$month] = 0;
        $dataCA[$month] = 0;
    }
} else {
    $start = $startDate ?: $today->format('Y-m-01');
    $end = $endDate ?: $today->format('Y-m-t');
    $period = new DatePeriod(new DateTime($start), new DateInterval('P1M'), (new DateTime($end))->modify('+1 month'));
    foreach($period as $dt){
        $month = $dt->format('Y-m');
        $months[$month] = $dt->format('m/Y');
        $dataImpayes[$month] = 0;
        $dataCA[$month] = 0;
    }
}

// Récupérer impayés par mois
$sqlImpayesMonth = "SELECT DATE_FORMAT(t.dateVente,'%Y-%m') AS mois, SUM(t.montant) AS total_impayes
                    FROM impaye i
                    JOIN transaction t ON i.id_transaction = t.id_transaction
                    WHERE t.id_commercant = :siren";

$paramsMonth = ['siren'=>$siren_commercant];

// Ajout des filtres
if($selectedMotif){
    $sqlImpayesMonth .= " AND i.id_motif = :motif";
    $paramsMonth['motif'] = $selectedMotif;
}

if($periodeGlissante == '4'){
    $sqlImpayesMonth .= " AND t.dateVente >= :start";
    $paramsMonth['start'] = (clone $today)->modify('-3 months')->format('Y-m-01');
} elseif($periodeGlissante == '12'){
    $sqlImpayesMonth .= " AND t.dateVente >= :start";
    $paramsMonth['start'] = (clone $today)->modify('-11 months')->format('Y-m-01');
} elseif($startDate){
    $sqlImpayesMonth .= " AND t.dateVente >= :start";
    $paramsMonth['start'] = $startDate;
}
if($endDate){
    $sqlImpayesMonth .= " AND t.dateVente <= :end";
    $paramsMonth['end'] = $endDate;
}

$sqlImpayesMonth .= " GROUP BY mois ORDER BY mois";
$stmt = $pdo->prepare($sqlImpayesMonth);
$stmt->execute($paramsMonth);
$impayesParMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($impayesParMonth as $row){
    $month = $row['mois'];
    if(isset($dataImpayes[$month])){
        $dataImpayes[$month] = (float)$row['total_impayes'];
    }
}

// Récupérer le chiffre d'affaire par mois
$sqlCAMonth = "SELECT DATE_FORMAT(dateVente,'%Y-%m') AS mois, SUM(montant) AS total_ca
               FROM transaction WHERE id_commercant=:siren";

$paramsCA = ['siren'=>$siren_commercant];

if($periodeGlissante == '4'){
    $sqlCAMonth .= " AND dateVente >= :start";
    $paramsCA['start'] = (clone $today)->modify('-3 months')->format('Y-m-01');
} elseif($periodeGlissante == '12'){
    $sqlCAMonth .= " AND dateVente >= :start";
    $paramsCA['start'] = (clone $today)->modify('-11 months')->format('Y-m-01');
} elseif($startDate){
    $sqlCAMonth .= " AND dateVente >= :start";
    $paramsCA['start'] = $startDate;
}
if($endDate){
    $sqlCAMonth .= " AND dateVente <= :end";
    $paramsCA['end'] = $endDate;
}

$sqlCAMonth .= " GROUP BY mois ORDER BY mois";
$stmt = $pdo->prepare($sqlCAMonth);
$stmt->execute($paramsCA);
$caParMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($caParMonth as $row){
    $month = $row['mois'];
    if(isset($dataCA[$month])){
        $dataCA[$month] = (float)$row['total_ca'];
    }
}

// Préparer les données pour les graphiques
$sqlImp = "SELECT DATE_FORMAT(t.dateVente, '%Y-%m') AS mois, SUM(t.montant) AS total_impayes
           FROM impaye i
           JOIN transaction t ON i.id_transaction = t.id_transaction
           WHERE t.id_commercant = :siren";

$params = ['siren' => $siren_commercant];
if($startDate){
    $sqlImp .= " AND t.dateVente >= :start";
    $params['start'] = $startDate;
}
if($endDate){
    $sqlImp .= " AND t.dateVente <= :end";
    $params['end'] = $endDate;
}
if($selectedMotif){
    $sqlImp .= " AND i.id_motif = :motif";
    $params['motif'] = $selectedMotif;
}

$sqlImp .= " GROUP BY mois ORDER BY mois";
$stmt = $pdo->prepare($sqlImp);
$stmt->execute($params);
$impayesParMois = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le chiffre d'affaire par mois
$sqlCA = "SELECT DATE_FORMAT(dateVente, '%Y-%m') AS mois, SUM(montant) AS ca_total
          FROM transaction
          WHERE id_commercant = :siren";

$caParams = ['siren' => $siren_commercant];
if($startDate){
    $sqlCA .= " AND dateVente >= :start";
    $caParams['start'] = $startDate;
}
if($endDate){
    $sqlCA .= " AND dateVente <= :end";
    $caParams['end'] = $endDate;
}

$sqlCA .= " GROUP BY mois ORDER BY mois";
$stmt = $pdo->prepare($sqlCA);
$stmt->execute($caParams);
$caParMois = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fusionner tous les mois pour aligner les datasets
$allMonths = array_unique(array_merge(
    array_column($impayesParMois, 'mois'),
    array_column($caParMois, 'mois')
));
sort($allMonths);

$dataImpayesByMonth = array_column($impayesParMois, 'total_impayes', 'mois');
$dataCAByMonth = array_column($caParMois, 'ca_total', 'mois');

$labels = $allMonths;
$dataImpayesFinal = [];
$dataCAFinal = [];

foreach($allMonths as $month){
    $dataImpayesFinal[] = isset($dataImpayesByMonth[$month]) ? (float)$dataImpayesByMonth[$month] : 0;
    $dataCAFinal[] = isset($dataCAByMonth[$month]) ? (float)$dataCAByMonth[$month] : 0;
}

// Préparer les variables pour le graphique
$labelsGraph = $allMonths;         
$impayesGraph = $dataImpayesFinal;  
$caGraph = $dataCAFinal;        

$chartType = $typeGraph; 
$chartLabels = $labelsGraph;
$chartImpayes = $impayesGraph;
$chartCA = $caGraph;


// ----------------------
// 6. Préparation des exports (CSV, XLS, PDF)
// ----------------------

// Export CSV, XLS et PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {
    $format = $_POST['format'];
    $title = "LISTE DES IMPAYÉS";
    $subtitle = "EXTRAIT DU ".date('d/m/Y');

    // Récupérer toutes les données filtrées
    $sql = "SELECT 
            i.numImpayé AS num_dossier,
            t.dateVente AS date_vente,
            r.dateRemise AS date_remise,
            c.numCarteClient AS num_carte,
            t.reseau AS reseau,
            t.devise,
            t.montant,
            m.libelle AS libelle_impaye
        FROM impaye i
        JOIN transaction t ON i.id_transaction = t.id_transaction
        LEFT JOIN remise r ON t.id_remise = r.id_remise
        LEFT JOIN clientfinal c ON t.id_client = c.id_client
        LEFT JOIN motif m ON i.id_motif = m.id_motif
        WHERE t.id_commercant = :siren";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['siren' => $siren_commercant]);
    $allImpaye = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //---------------- CSV ----------------
    if ($format === 'csv') {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=impayes.csv");
        $out = fopen("php://output", 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, [$title], ';');
        fputcsv($out, [$subtitle], ';');
        fputcsv($out, []);
        fputcsv($out, ['Date vente','Date remise','N° Carte','Réseau','N° dossier','Devise','Montant','Libellé'], ';');
        foreach($allImpaye as $i){
            fputcsv($out, [
                $i['date_vente'],
                $i['date_remise'],
                '************'.substr($i['num_carte'],-4),
                $i['reseau'],
                $i['num_dossier'],
                $i['devise'],
                $i['montant'],
                $i['libelle_impaye']
            ], ';');
        }
        fclose($out);
        exit();
    }

    //---------------- XLS ----------------
    if ($format === 'xls') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=impayes.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "\xEF\xBB\xBF";
        echo "<table border='1'>";
        echo "<tr><th colspan='8'>$title</th></tr>";
        echo "<tr><td colspan='8'>$subtitle</td></tr>";
        echo "<tr><th>Date vente</th><th>Date remise</th><th>N° Carte</th><th>Réseau</th><th>N° dossier</th><th>Devise</th><th>Montant</th><th>Libellé</th></tr>";
        foreach($allImpaye as $i){
            echo "<tr>
                    <td>{$i['date_vente']}</td>
                    <td>{$i['date_remise']}</td>
                    <td>************".substr($i['num_carte'],-4)."</td>
                    <td>{$i['reseau']}</td>
                    <td>{$i['num_dossier']}</td>
                    <td>{$i['devise']}</td>
                    <td>".number_format($i['montant'],2,',',' ')."</td>
                    <td>{$i['libelle_impaye']}</td>
                  </tr>";
        }
        echo "</table>";
        exit();
    }

    //---------------- PDF ----------------
    if ($format === 'pdf') {
        require_once __DIR__ . '/../fpdf/fpdf.php';

        $title = "LISTE DES IMPAYÉS";
        $dateExtraction = "EXTRAIT DU " . date('d/m/Y');

        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10, utf8_decode($title),0,1,'C');

        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0,8, utf8_decode($dateExtraction),0,1,'C');
        $pdf->Ln(5);

        if (!empty($_POST['chart_image1']) && !empty($_POST['chart_image2'])) {

            $charts = [];
            foreach (['chart_image1', 'chart_image2'] as $key) {
                $img = str_replace('data:image/png;base64,', '', $_POST[$key]);
                $img = base64_decode($img);
                $tmp = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
                file_put_contents($tmp, $img);
                $charts[$key] = $tmp;
            }

            // Insérer les images dans le PDF
            $width = 120;
            $pdf->Image($charts['chart_image1'], 10, 10, $width);
            $pdf->Image($charts['chart_image2'], 140, 40, $width);

            $imageHeight = $width * 0.75; 
            $pdf->SetY($y + $imageHeight + 5);

            // Supprimer les fichiers temporaires
            unlink($charts['chart_image1']);
            unlink($charts['chart_image2']);
        }

        // En-têtes tableau
        $pdf->SetFont('Arial','B',9);
        $headers = ['Date vente','Date remise','N° Carte','Réseau','N° dossier','Devise','Montant','Libellé'];
        $widths  = [30,30,35,25,30,20,30,60];

        foreach($headers as $k => $h){
            $pdf->Cell($widths[$k],8, utf8_decode($h),1);
        }
        $pdf->Ln();

        // Données impayés
        $pdf->SetFont('Arial','',9);
        foreach($impayes as $i){
            $pdf->Cell(30,8, utf8_decode($i['date_vente'] ?? ''),1);
            $pdf->Cell(30,8, utf8_decode($i['date_remise'] ?? ''),1);
            $pdf->Cell(35,8, utf8_decode('************'.substr($i['num_carte'] ?? '',-4)),1);
            $pdf->Cell(25,8, utf8_decode($i['reseau'] ?? ''),1);
            $pdf->Cell(30,8, utf8_decode($i['num_dossier'] ?? ''),1);
            $pdf->Cell(20,8, utf8_decode($i['devise'] ?? ''),1);

            // Montant en rouge si négatif
            if (($i['montant'] ?? 0) < 0) {
                $pdf->SetTextColor(255,0,0);
            }
            $pdf->Cell(30,8, utf8_decode($i['montant'] ?? ''),1);
            $pdf->SetTextColor(0,0,0);

            $pdf->Cell(60,8, utf8_decode($i['libelle_impaye'] ?? ''),1);
            $pdf->Ln();
        }

        $pdf->Output('D','impayes.pdf');
        exit();
    }
}