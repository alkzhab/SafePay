<?php
/**
 * po_unpaid.php
 *
 * Description:
 * Gestion des impayés
 *
 * - Vérifie la session et le rôle PO
 * - Récupère la liste des impayés avec filtres
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
// 2. Récupération des impayés avec filtres
// ----------------------

// Filtres
$searchSiren = trim($_GET['siren'] ?? '');
$searchRaison = trim($_GET['raison'] ?? '');
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$searchNumDossier = trim($_GET['num_dossier'] ?? '');

// Construction de la requête
$where = " WHERE 1 ";
$params = [];

if($searchSiren) { 
    $where .= " AND c.siren LIKE :siren "; $params['siren']="%$searchSiren%"; 
}

if($searchRaison) { 
    $where .= " AND c.raison_sociale LIKE :raison "; $params['raison']="%$searchRaison%"; 
}

if($startDate) { 
    $where .= " AND t.dateVente >= :start "; $params['start']=$startDate; 
}

if($endDate) { 
    $where .= " AND t.dateVente <= :end "; $params['end']=$endDate; 
}

if($searchNumDossier) { 
    $where .= " AND i.numImpayé LIKE :numdossier "; $params['numdossier']="%$searchNumDossier%"; 
}

// Liste des commerçants pour les filtres
$stmtCommercants = $pdo->query("
    SELECT siren, raison_sociale
    FROM commercant
    ORDER BY raison_sociale
");
$commercants = $stmtCommercants->fetchAll(PDO::FETCH_ASSOC);


// Requête principale
$sql = "SELECT 
            c.siren,
            c.raison_sociale,
            t.dateVente,
            r.dateRemise,
            cfc.numCarteClient,
            t.reseau,
            i.numImpayé,
            t.devise,
            t.montant,
            m.libelle AS libelle_impaye
        FROM impaye i
        JOIN transaction t ON i.id_transaction = t.id_transaction
        LEFT JOIN remise r ON t.id_remise = r.id_remise
        LEFT JOIN clientfinal cfc ON t.id_client = cfc.id_client
        LEFT JOIN motif m ON i.id_motif = m.id_motif
        JOIN commercant c ON t.id_commercant = c.siren
        $where
        ORDER BY t.dateVente DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$impayes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total impayés
$totalImpayes = array_sum(array_column($impayes,'montant'));

// Répartition des impayés par motif (pour le camembert)
$sqlMotifs = "SELECT m.libelle, SUM(t.montant) AS total
              FROM impaye i
              JOIN transaction t ON i.id_transaction = t.id_transaction
              JOIN motif m ON i.id_motif = m.id_motif
              GROUP BY m.libelle
              ORDER BY total DESC";
$stmtMotifs = $pdo->prepare($sqlMotifs);
$stmtMotifs->execute();
$motifs = $stmtMotifs->fetchAll(PDO::FETCH_ASSOC);


// Charger les numéros d'impayés pour le select
$sqlImpayes = "SELECT DISTINCT numImpayé FROM impaye ORDER BY numImpayé ASC";
$stmtImpayes = $pdo->query($sqlImpayes);
$listeImpayes = $stmtImpayes->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// 3. Gestion des exports
// ----------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {

    if(empty($impayes)){
        die("Aucune donnée à exporter.");
    }

    $format = $_POST['format'];
    $title = "LISTE DES IMPAYES - SAFEPAY";
    $dateExtraction = "EXTRAIT DU " . date('d/m/Y');

    switch ($format) {

        // ================= CSV =================
        case 'csv':
            header("Content-Type: text/csv; charset=UTF-8");
            header("Content-Disposition: attachment; filename=impayes.csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); // UTF-8 Excel

            fputcsv($output, [$title]);
            fputcsv($output, [$dateExtraction]);
            fputcsv($output, []);
            fputcsv($output, [
                "SIREN","RAISON SOCIALE","DATE VENTE","DATE REMISE",
                "CARTE CLIENT","RESEAU","NUM DOSSIER","DEVISE",
                "MONTANT","MOTIF"
            ], ';');

            foreach($impayes as $i){
                fputcsv($output, [
                    $i['siren'],
                    $i['raison_sociale'],
                    $i['dateVente'],
                    $i['dateRemise'],
                    $i['numCarteClient'],
                    $i['reseau'],
                    $i['numImpayé'],
                    $i['devise'],
                    $i['montant'],
                    $i['libelle_impaye']
                ], ';');
            }

            fclose($output);
            exit();


        // ================= XLS =================
        case 'xls':
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=impayes.xls");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo "\xEF\xBB\xBF";

            echo "<table border='1'>";
            echo "<tr><th colspan='10'>$title</th></tr>";
            echo "<tr><td colspan='10'>$dateExtraction</td></tr>";

            echo "
            <tr>
                <th>SIREN</th><th>RAISON SOCIALE</th><th>DATE VENTE</th><th>DATE REMISE</th>
                <th>CARTE CLIENT</th><th>RESEAU</th><th>NUM DOSSIER</th><th>DEVISE</th>
                <th>MONTANT</th><th>MOTIF</th>
            </tr>";

            foreach($impayes as $i){
                echo '<tr>';
                    echo '<td>' . htmlspecialchars($i['siren']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['raison_sociale']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['dateVente']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['dateRemise']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['numCarteClient']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['reseau']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['numImpayé']) . '</td>';
                    echo '<td>' . htmlspecialchars($i['devise']) . '</td>';

                    echo '<td style="color:red; font-weight:bold;">' . htmlspecialchars($i['montant']) . '</td>';

                    echo '<td>' . htmlspecialchars($i['libelle_impaye']) . '</td>';
                echo '</tr>';
            }

            echo "</table>";
            exit();


        // ================= PDF =================
        case 'pdf':
            require_once __DIR__ . '/../fpdf/fpdf.php';
            $pdf = new FPDF('L','mm','A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',14);
            $pdf->Cell(0,10, utf8_decode($title),0,1,'C');
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(0,8, utf8_decode($dateExtraction),0,1,'C');
            $pdf->Ln(5);
            // Insertion graphique
            if (!empty($_POST['chart_image'])) {

                $img = str_replace('data:image/png;base64,', '', $_POST['chart_image']);
                $img = base64_decode($img);

                $tmp = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
                file_put_contents($tmp, $img);

                // Insérer l'image générée
                // Largeur réelle de l’image
                $imageWidth = 130;

                // Insère image
                $yImage = $pdf->GetY();
                $pdf->Image($tmp, 10, $yImage, $imageWidth);

                // Calcule hauteur réelle de l'image pour sauter correctement
                $imageHeight = 0.75 * $imageWidth;

                // Redescendre sous l'image
                $pdf->SetY($yImage + $imageHeight + 5);

                unlink($tmp);

            }


            // En-têtes
            $pdf->SetFont('Arial','B',9);
            $headers = ["SIREN","RAISON","DATE VENTE","DATE REMISE","CARTE","RESEAU","DOSSIER","DEV","MONTANT","MOTIF"];
            $widths  = [25,45,25,25,30,25,30,15,25,45];

            foreach($headers as $k => $h){
                $pdf->Cell($widths[$k],8, utf8_decode($h),1);
            }
            $pdf->Ln();

            // Données
            $pdf->SetFont('Arial','',9);
            foreach($impayes as $i){
                $pdf->Cell(25,8, utf8_decode($i['siren']),1);
                $pdf->Cell(45,8, utf8_decode($i['raison_sociale']),1);
                $pdf->Cell(25,8, utf8_decode($i['dateVente']),1);
                $pdf->Cell(25,8, utf8_decode($i['dateRemise']),1);
                $pdf->Cell(30,8, utf8_decode($i['numCarteClient']),1);
                $pdf->Cell(25,8, utf8_decode($i['reseau']),1);
                $pdf->Cell(30,8, utf8_decode($i['numImpayé']),1);
                $pdf->Cell(15,8, utf8_decode($i['devise']),1);

                // Montant en rouge
                $pdf->SetTextColor(255,0,0);
                $pdf->Cell(25,8, utf8_decode($i['montant']),1);
                $pdf->SetTextColor(0,0,0);

                $pdf->Cell(45,8, utf8_decode($i['libelle_impaye']),1);
                $pdf->Ln();
            }

            $pdf->Output('D','impayes.pdf');
            exit();

    }
}