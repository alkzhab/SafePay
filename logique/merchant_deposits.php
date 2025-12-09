<?php
/**
 * merchant_deposits.php
 *
 * Description:
 * Gestion de la page des remises du commerçant
 *
 * - Vérifie la session et le rôle COMMERCANT
 * - Récupère le commerçant connecté
 * - Gère la pagination et la recherche des remises
 * - Permet l'export des remises et du détail d'une remise en CSV, XLS, PDF
 * 
 * 
 */

// ----------------------
// 1. Vérification de session et rôle
// ----------------------

session_start();
require_once __DIR__ . '/../config/db.php';

// Redirection si utilisateur non connecté
if (!isset($_SESSION['user_login'])) {
    header('Location: index.php');
    exit();
}

$login = $_SESSION['user_login'];

// ----------------------
// 2. Récupération du commerçant connecté
// ----------------------

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login LIMIT 1");
$stmt->execute(['login' => $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier le rôle
if (!$user || $user['role'] !== 'COMMERCANT') {
    header('Location: index.php');
    exit();
}

// Récupérer le commerçant
$stmt = $pdo->prepare("SELECT * FROM commercant WHERE id_utilisateur = :id LIMIT 1");
$stmt->execute(['id' => $user['id_utilisateur']]);
$commercant = $stmt->fetch(PDO::FETCH_ASSOC);

$id_commercant = $commercant['siren'];

// ----------------------
// 3. Gestion de la pagination, recherche et export
// ----------------------

// Paramètres de pagination et recherche
$perPage = max(1, (int)($_GET['per_page'] ?? 10)); // éléments par page
$page = max(1, (int)($_GET['page'] ?? 1));         // page courante
$search = trim($_GET['search'] ?? '');            // filtre recherche

// Récupérer le nombre total de remises pour la pagination
$sqlCount = "SELECT COUNT(*) FROM remise WHERE id_commercant = :id";
$paramsCount = ['id' => $id_commercant];

if ($search) {
    $sqlCount .= " AND numRemise LIKE :search";
    $paramsCount['search'] = "%$search%";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($paramsCount);
$totalRemises = $stmt->fetchColumn();

// Récupérer les remises avec pagination et recherche
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM remise WHERE id_commercant = :id";
$params = ['id' => $id_commercant];

if ($search) {
    $sql .= " AND numRemise LIKE :search";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY dateRemise DESC LIMIT :offset, :perPage";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id_commercant, PDO::PARAM_STR);

if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);

$stmt->execute();
$remises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer le nombre total de pages
$totalPages = ceil($totalRemises / $perPage);

// Récupérer toutes les remises du commerçant (pour export)
$stmtAll = $pdo->prepare("SELECT * FROM remise WHERE id_commercant = :id ORDER BY dateRemise DESC");
$stmtAll->execute(['id' => $id_commercant]);
$allRemises = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les transactions par remise pour le commerçant
$transactionsByRemise = [];
$stmtTx = $pdo->prepare("
    SELECT t.*, c.reseauClient, c.numCarteClient
    FROM transaction t
    JOIN clientfinal c ON c.id_client = t.id_client
    WHERE t.id_remise = :id
    ORDER BY t.dateVente
");

foreach ($allRemises as $r) {
    $stmtTx->execute(['id' => $r['id_remise']]);
    $transactionsByRemise[$r['id_remise']] = $stmtTx->fetchAll(PDO::FETCH_ASSOC);
}

// ----------------------
// 4. Gestion des exports
// ----------------------

// EXPORT DETAIL D'UNE REMISE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_remise'])) {
    $idRemise = $_POST['id_remise'];
    $format = $_POST['format'];

    // Récupérer la remise
    $stmt = $pdo->prepare("SELECT * FROM remise WHERE id_remise = :id");
    $stmt->execute(['id'=>$idRemise]);
    $remise = $stmt->fetch(PDO::FETCH_ASSOC);

    $transactions = $transactionsByRemise[$idRemise] ?? [];

    $title = "DETAIL REMISE N° ".$remise['numRemise'];
    $subtitle = "Commerçant : ".$commercant['raison_sociale'];

    //---------------- CSV ----------------
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

    //---------------- XLS ----------------
    if ($format === 'xls') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=remise_$idRemise.xls");
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

    //---------------- PDF ----------------
    if ($format === 'pdf') {
        require_once __DIR__ . '/../fpdf/fpdf.php';
        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,$title,0,1,'C');
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(0,8,$subtitle,0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        foreach(['DATE','CARTE','RESEAU','AUTO','DEVISE','MONTANT','SENS'] as $col){
            $pdf->Cell(40,8,$col,1);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        foreach ($transactions as $t) {
            $pdf->Cell(40,8,$t['dateVente'],1);
            $pdf->Cell(40,8,$t['numCarteClient'],1);
            $pdf->Cell(40,8,$t['reseauClient'],1);
            $pdf->Cell(40,8,$t['numAutorisation'],1);
            $pdf->Cell(40,8,$t['devise'],1);
            if ($t['sens'] === '-') {
                $pdf->SetTextColor(255,0,0);
            }
            $pdf->Cell(40,8,number_format($t['montant'],2,',',' '),1);
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(40,8,$t['sens'],1);
            $pdf->Ln();
        }

        $pdf->Output('D',"remise_$idRemise.pdf");
        exit();
    }
}



// ---------------- EXPORT ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {
    $format = $_POST['format'];
    $title = "LISTE DES REMISES - MON COMPTE";
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
        fputcsv($out, []);

        fputcsv($out, ['NUM REMISE','DATE REMISE','NB TRANSACTIONS','MONTANT TOTAL','SENS'], ';');

        foreach ($allRemises as $r) {
            // Calcul nb_transactions et montant_total
            $stmtTx = $pdo->prepare("SELECT COUNT(*) as nb_tx, SUM(CASE WHEN sens='+' THEN montant ELSE -montant END) as total FROM transaction WHERE id_remise=:id");
            $stmtTx->execute(['id'=>$r['id_remise']]);
            $txData = $stmtTx->fetch(PDO::FETCH_ASSOC);

            fputcsv($out, [
                $r['numRemise'],
                $r['dateRemise'],
                $txData['nb_tx'],
                number_format($txData['total'],2,'.',''),
                $txData['total']>=0?'+':'-'
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
        echo "<tr><th colspan='5'>".$title."</th></tr>";
        echo "<tr><td colspan='5'>".$subtitle."</td></tr>";
        echo "<tr><th>NUM REMISE</th><th>DATE REMISE</th><th>NB TX</th><th>MONTANT</th><th>SENS</th></tr>";

        foreach ($allRemises as $r) {
            $stmtTx = $pdo->prepare("SELECT COUNT(*) as nb_tx, SUM(CASE WHEN sens='+' THEN montant ELSE -montant END) as total FROM transaction WHERE id_remise=:id");
            $stmtTx->execute(['id'=>$r['id_remise']]);
            $txData = $stmtTx->fetch(PDO::FETCH_ASSOC);

            echo "<tr>
                    <td>{$r['numRemise']}</td>
                    <td>{$r['dateRemise']}</td>
                    <td>{$txData['nb_tx']}</td>
                    <td>".number_format($txData['total'],2,'.','')."</td>
                    <td>".($txData['total']>=0?'+':'-')."</td>
                  </tr>";
        }
        echo "</table>";
        exit();
    }

    //---------------- PDF ----------------
    if ($format === 'pdf') {
        require_once __DIR__ . '/../fpdf/fpdf.php';
        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1//TRANSLIT',$title),0,1,'C');
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1//TRANSLIT',$subtitle),0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(40,8,'NUM REMISE',1);
        $pdf->Cell(40,8,'DATE REMISE',1);
        $pdf->Cell(30,8,'NB TX',1);
        $pdf->Cell(40,8,'MONTANT',1);
        $pdf->Cell(20,8,'SENS',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        foreach ($allRemises as $r) {
            $stmtTx = $pdo->prepare("SELECT COUNT(*) as nb_tx, SUM(CASE WHEN sens='+' THEN montant ELSE -montant END) as total FROM transaction WHERE id_remise=:id");
            $stmtTx->execute(['id'=>$r['id_remise']]);
            $txData = $stmtTx->fetch(PDO::FETCH_ASSOC);

            $pdf->Cell(40,8,$r['numRemise'],1);
            $pdf->Cell(40,8,$r['dateRemise'],1);
            $pdf->Cell(30,8,$txData['nb_tx'],1);
            if ($t['sens'] === '-') {
                $pdf->SetTextColor(255,0,0);
            }
            $pdf->Cell(40,8,number_format($t['montant'],2,',',' '),1);
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(20,8,$txData['total']>=0?'+':'-',1);
            $pdf->Ln();
        }
        $pdf->Output('D','remises.pdf');
        exit();
    }
}