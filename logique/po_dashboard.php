<?php
/**
 * po_dashboard.php
 *
 * Description:
 * Gestion de la page de tableau de bord du Product Owner
 *
 * - Vérifie la session et le rôle PO
 * - Récupère les comptes clients en fonction des filtres
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
// 2. Récupération des comptes clients avec filtres
// ----------------------

// Filtres
$searchSiren = trim($_GET['siren'] ?? '');
$searchRaison = trim($_GET['raison'] ?? '');
$dateValeur = $_GET['date_valeur'] ?? '';

// Construction de la requête
$where = " WHERE 1 ";
$params = [];

if($searchSiren) { 
    $where .= " AND c.siren LIKE :siren "; 
    $params['siren'] = "%$searchSiren%"; 
}
if($searchRaison) { 
    $where .= " AND c.raison_sociale LIKE :raison "; 
    $params['raison'] = "%$searchRaison%"; 
}
if($dateValeur) { 
    $where .= " AND t.dateVente = :dateval "; 
    $params['dateval'] = $dateValeur; 
}

// Requête pour les comptes clients
$sql = "SELECT 
            c.siren,
            c.raison_sociale,
            COUNT(t.id_transaction) AS nb_transactions,
            t.devise,
            SUM(t.montant) AS montant_total
        FROM transaction t
        JOIN commercant c ON t.id_commercant = c.siren
        $where
        GROUP BY c.siren, c.raison_sociale, t.devise
        ORDER BY c.siren ASC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
$raisonOptions = $pdo->query("SELECT DISTINCT raison_sociale FROM commercant ORDER BY raison_sociale ASC")->fetchAll(PDO::FETCH_COLUMN);
$sirenOptions = $pdo->query("SELECT DISTINCT siren FROM commercant ORDER BY siren ASC")->fetchAll(PDO::FETCH_COLUMN);

// ----------------------
// 3. Gestion des exports
// ----------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {
    $format = $_POST['format'];

    // Titres
    $title = "LISTE DES COMMERCANTS - SAFEPAY";
    $dateExtraction = "EXTRAIT DU " . date('d/m/Y');

    if(empty($annonces)){
        die("Aucune donnée à exporter.");
    }

    switch ($format) {
        case 'csv':
            header("Content-Type: text/csv; charset=UTF-8");
            header("Content-Disposition: attachment; filename=comptes_client.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, [$title]);
            fputcsv($output, [$dateExtraction]);
            fputcsv($output, []);
            fputcsv($output, ["SIREN","RAISON SOCIALE","NOMBRE DE TRANSACTIONS","DEVISE","MONTANT TOTAL"], ';');
            foreach($annonces as $a){
                fputcsv($output, [
                    $a['siren'],
                    $a['raison_sociale'],
                    $a['nb_transactions'],
                    $a['devise'],
                    $a['montant_total']
                ], ';');
            }
            fclose($output);
            exit();

        case 'xls':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="comptes_clients.xls"');
            echo "<table border='1'><tr><th colspan='5'>$title</th></tr>";
            echo "<tr><td colspan='5'>$dateExtraction</td></tr>";
            echo "<tr><th>SIREN</th><th>RAISON SOCIALE</th><th>NOMBRE DE TRANSACTIONS</th><th>DEVISE</th><th>MONTANT TOTAL</th></tr>";
            foreach($annonces as $a){
                echo "<tr>
                        <td>{$a['siren']}</td>
                        <td>{$a['raison_sociale']}</td>
                        <td>{$a['nb_transactions']}</td>
                        <td>{$a['devise']}</td>
                        <td>{$a['montant_total']}</td>
                      </tr>";
            }
            echo "</table>";
            exit();

        case 'pdf':
            require_once __DIR__ . '/../fpdf/fpdf.php';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,10,$title,0,1,'C');
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(0,8,$dateExtraction,0,1,'C');
            $pdf->Ln(5);
            // Tableau PDF
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(30,8,'SIREN',1);
            $pdf->Cell(50,8,'RAISON SOCIALE',1);
            $pdf->Cell(30,8,'TRANSACTION',1);
            $pdf->Cell(20,8,'DEVISE',1);
            $pdf->Cell(35,8,'MONTANT TOTAL',1);
            $pdf->Ln();
            $pdf->SetFont('Arial','',10);
            foreach($annonces as $a){
                $pdf->Cell(30,8,$a['siren'],1);
                $pdf->Cell(50,8,$a['raison_sociale'],1);
                $pdf->Cell(30,8,$a['nb_transactions'],1);
                $pdf->Cell(20,8,$a['devise'],1);
                $pdf->Cell(35,8,$a['montant_total'],1);
                $pdf->Ln();
            }
            $pdf->Output('D','comptes_clients.pdf');
            exit();
    }
}