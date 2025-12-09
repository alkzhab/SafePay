<?php
/**
 * po_deposits.php
 *
 * Description:
 * Gestion de tout les remises des commerçants
 *
 * - Vérifie la session et le rôle PO
 * - Récupère les remises avec filtres
 * - Récupère toutes les transactions par remise
 * - Gère l'export des données en CSV, XLS et PDF
 * 
 * 
 */

// ----------------------
// 1. Vérification de session et rôle
// ----------------------

session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'PO'){
    header('Location:index.php');
    exit();
}

// ----------------------
// 2. Récupération des remises avec filtres
// ----------------------

// Filtres
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$selectedSiren = $_GET['siren'] ?? '';
$selectedRaison = $_GET['raison'] ?? '';

// Récupération des commerçants pour filtre
$stmt = $pdo->query("SELECT siren, raison_sociale FROM commercant ORDER BY raison_sociale");
$commercants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparation requête remises
$where = " WHERE 1 ";
$params = [];

if($startDate) { 
    $where .= " AND r.dateRemise >= :start "; $params['start']=$startDate; 
}

if($endDate) { 
    $where .= " AND r.dateRemise <= :end "; $params['end']=$endDate; 
}

if($selectedSiren) { 
    $where .= " AND r.id_commercant = :siren "; $params['siren']=$selectedSiren; 
}

if($selectedRaison) { 
    $where .= " AND c.raison_sociale = :raison "; $params['raison']=$selectedRaison; 
}

$sql = "SELECT r.*, c.raison_sociale,
       (SELECT COUNT(*) FROM transaction t WHERE t.id_remise=r.id_remise) as nb_transactions,
       (SELECT SUM(CASE WHEN t.sens='+' THEN t.montant
                        WHEN t.sens='-' THEN -t.montant END)
        FROM transaction t WHERE t.id_remise=r.id_remise) as montant_total
        FROM remise r
        JOIN commercant c ON c.siren = r.id_commercant
        $where
        ORDER BY r.dateRemise DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$remises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// 3. Récupération des transactions par remise
// ----------------------

// Récupérer toutes les transactions par remise
$transactionsByRemise = [];

$stmtDetail = $pdo->prepare("
    SELECT t.*, c.reseauClient, c.numCarteClient
    FROM transaction t
    JOIN clientfinal c ON c.id_client = t.id_client
    WHERE t.id_remise = :id
    ORDER BY t.dateVente
");

foreach ($remises as $r) {
    $stmtDetail->execute(['id' => $r['id_remise']]);
    $transactionsByRemise[$r['id_remise']] = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
}

// ----------------------
// 4. Gestion des exports
// ----------------------

// EXPORT DETAIL D'UNE REMISE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remise'])) {

    $idRemise = $_POST['id_remise'];
    $format = $_POST['format'];

    // Infos remise
    $stmt = $pdo->prepare("
        SELECT r.*, c.raison_sociale
        FROM remise r
        JOIN commercant c ON c.siren = r.id_commercant
        WHERE r.id_remise = ?
    ");
    $stmt->execute([$idRemise]);
    $remise = $stmt->fetch(PDO::FETCH_ASSOC);

    // Transactions
    $stmt = $pdo->prepare("
        SELECT t.*, c.reseauClient, c.numCarteClient
        FROM transaction t
        JOIN clientfinal c ON c.id_client = t.id_client
        WHERE t.id_remise = ?
        ORDER BY t.dateVente
    ");
    $stmt->execute([$idRemise]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $title = "DETAIL REMISE N° ".$remise['numRemise'];
    $subtitle = "Commerçant : ".$remise['raison_sociale'];

    // ---------------- CSV ----------------
    if ($format === 'csv') {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=remise_$idRemise.csv");
        $out = fopen("php://output", 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, [$title], ';');
        fputcsv($out, [$subtitle], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['DATE','CARTE','RESEAU','AUTORISATION','DEVISE','MONTANT','SENS'], ';');

        foreach ($transactions as $t) {
            fputcsv($out, [
                $t['dateVente'],
                $t['numCarteClient'],
                $t['reseauClient'],
                $t['numAutorisation'],
                $t['devise'],
                $t['montant'],
                $t['sens']
            ], ';');
        }
        fclose($out);
        exit();
    }

    // ---------------- XLS ----------------
    if ($format === 'xls') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=remise_$idRemise.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "\xEF\xBB\xBF";

        echo "<table border='1'>";
        echo "<tr><th colspan='7'>".htmlspecialchars($title)."</th></tr>";
        echo "<tr><td colspan='7'>".htmlspecialchars($subtitle)."</td></tr>";
        echo "<tr>
                <th>DATE</th><th>CARTE</th><th>RESEAU</th>
                <th>AUTO</th><th>DEVISE</th><th>MONTANT</th><th>SENS</th>
              </tr>";

        foreach ($transactions as $t) {
            echo "<tr>
                    <td>".htmlspecialchars($t['dateVente'])."</td>
                    <td>".htmlspecialchars($t['numCarteClient'])."</td>
                    <td>".htmlspecialchars($t['reseauClient'])."</td>
                    <td>".htmlspecialchars($t['numAutorisation'])."</td>
                    <td>".htmlspecialchars($t['devise'])."</td>
                    <td>".number_format($t['montant'],2,',',' ')."</td>
                    <td>".htmlspecialchars($t['sens'])."</td>
                 </tr>";
        }
        echo "</table>";
        exit();
    }

    // ---------------- PDF ----------------
    if ($format === 'pdf') {
        require_once __DIR__ . '/../fpdf/fpdf.php';

        $pdf = new FPDF('L');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $title), 0, 1, 'C');
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(0,8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subtitle), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        foreach(['DATE','CARTE','RESEAU','AUTO','DEV','MONTANT','SENS'] as $col){
            $pdf->Cell(40,8,$col,1);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        foreach($transactions as $t){
            $pdf->Cell(40,8,$t['dateVente'],1);
            $pdf->Cell(40,8,$t['numCarteClient'],1);
            $pdf->Cell(40,8,$t['reseauClient'],1);
            $pdf->Cell(40,8,$t['numAutorisation'],1);
            $pdf->Cell(40,8,$t['devise'],1);
            $pdf->Cell(40,8,$t['montant'],1);
            $pdf->Cell(40,8,$t['sens'],1);
            $pdf->Ln();
        }

        $pdf->Output('D',"remise_$idRemise.pdf");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {

    $format = $_POST['format'];

    $title = "LISTE DES REMISES";
    $subtitle = "EXTRAIT DU " . date('d/m/Y');

    //---------------- CSV ----------------
    if ($format === 'csv') {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=remises.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($out, [$title], ';');
        fputcsv($out, [$subtitle], ';');
        fputcsv($out, [], ';');

        // En-tête
        fputcsv($out, [
            'SIREN', 'RAISON SOCIALE', 'NUM REMISE', 'DATE REMISE',
            'NB TRANSACTIONS', 'DEVISE', 'MONTANT', 'SENS'
        ], ';');

        // Données
        foreach ($remises as $r) {
            fputcsv($out, [
                $r['id_commercant'],
                $r['raison_sociale'],
                $r['numRemise'],
                $r['dateRemise'],
                $r['nb_transactions'],
                'EUR',
                number_format($r['montant_total'],2,'.',''),
                $r['montant_total'] >= 0 ? '+' : '-'
            ], ';');
        }

        fclose($out);
        exit();
    }

    //---------------- XLS ----------------
    if ($format === 'xls') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=remises.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
        echo "<tr><th colspan='8'>$title</th></tr>";
        echo "<tr><td colspan='8'>$subtitle</td></tr>";
        echo "<tr>
                <th>SIREN</th><th>RAISON</th><th>NUM REMISE</th><th>DATE</th>
                <th>NB TX</th><th>DEVISE</th><th>MONTANT</th><th>SENS</th>
             </tr>";

        foreach ($remises as $r) {
            echo "<tr>
                  <td>{$r['id_commercant']}</td>
                  <td>{$r['raison_sociale']}</td>
                  <td>{$r['numRemise']}</td>
                  <td>{$r['dateRemise']}</td>
                  <td>{$r['nb_transactions']}</td>
                  <td>EUR</td>
                  <td>{$r['montant_total']}</td>
                  <td>".($r['montant_total'] >= 0 ? '+' : '-')."</td>
                </tr>";
        }

        echo "</table>";
        exit();
    }

    //---------------- PDF ----------------
    if ($format === 'pdf') {
        require_once __DIR__ . '/../fpdf/fpdf.php';

        $pdf = new FPDF('L');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,$title,0,1,'C');
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(0,8,$subtitle,0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30,8,'SIREN',1);
        $pdf->Cell(50,8,'RAISON',1);
        $pdf->Cell(30,8,'REM.',1);
        $pdf->Cell(30,8,'DATE',1);
        $pdf->Cell(25,8,'NB TX',1);
        $pdf->Cell(20,8,'DEV.',1);
        $pdf->Cell(30,8,'MONTANT',1);
        $pdf->Cell(20,8,'SENS',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        foreach ($remises as $r) {
            $pdf->Cell(30,8,$r['id_commercant'],1);
            $pdf->Cell(50,8,$r['raison_sociale'],1);
            $pdf->Cell(30,8,$r['numRemise'],1);
            $pdf->Cell(30,8,$r['dateRemise'],1);
            $pdf->Cell(25,8,$r['nb_transactions'],1);
            $pdf->Cell(20,8,'EUR',1);
            $pdf->Cell(30,8,number_format($r['montant_total'],2),1);
            $pdf->Cell(20,8,$r['montant_total']>=0?'+':'-',1);
            $pdf->Ln();
        }

        $pdf->Output('D','remises.pdf');
        exit();
    }
}