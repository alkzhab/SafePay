<?php
/**
 * merchant_dashboard.php
 *
 * Description:
 * Gestion de la page de tableau de bord du commerçant
 *
 * - Vérifie la session et le rôle COMMERCANT
 * - Récupère les informations du commerçant
 * - Calcule le solde actuel
 * - Prépare les données pour le graphique d'évolution du solde mensuel
 * 
 * 
 */

// ----------------------
// 1. Vérification de session et rôle
// ----------------------

session_start();
require_once __DIR__ . '/../config/db.php';

// Vérifier que l'utilisateur est connecté
if(!isset($_SESSION['user_login'])){
    header('Location: ../public/index.php');
    exit();
}

$login = $_SESSION['user_login'];

// ----------------------
// 2. Récupération des informations du commerçant
// ----------------------

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login LIMIT 1");
$stmt->execute(['login' => $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'existe pas ou n'est pas commerçant, rediriger vers la page de connexion
if(!$user || $user['role'] !== 'COMMERCANT'){
    header('Location: ../public/index.php');
    exit();
}

// Récupère les infos du commerçant
$stmt = $pdo->prepare("SELECT * FROM commercant WHERE id_utilisateur = :id LIMIT 1");
$stmt->execute(['id' => $user['id_utilisateur']]);
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$merchant){
    die("Commerçant introuvable.");
}

// ----------------------
// 3. Calcul du solde actuel et préparation des données pour le graphique
// ----------------------

$siren = $merchant['siren'];

// --- Solde actuel ---
$stmt = $pdo->prepare("
    SELECT SUM(
        CASE t.sens 
            WHEN '+' THEN t.montant 
            WHEN '-' THEN -t.montant 
        END
    ) AS balance
    FROM transaction t
    LEFT JOIN impaye i ON t.id_transaction = i.id_transaction
    WHERE t.id_commercant = :siren AND i.numImpayé IS NULL
");
$stmt->execute(['siren' => $siren]);
$currentBalance = (float)$stmt->fetchColumn();

// --- Graphique évolution par mois ---
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(t.dateVente,'%Y-%m') AS month,
           SUM(CASE t.sens WHEN '+' THEN t.montant WHEN '-' THEN -t.montant END) AS total
    FROM transaction t
    LEFT JOIN impaye i ON t.id_transaction = i.id_transaction
    WHERE t.id_commercant = :siren AND i.numImpayé IS NULL
    GROUP BY month
    ORDER BY month
");
$stmt->execute(['siren'=>$siren]);
$monthData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$balanceGraph = [];
$cumulative = 0;

if(empty($monthData)){
    $labels[] = date('Y-m');
    $balanceGraph[] = $currentBalance;
} else {
    foreach($monthData as $row){
        $cumulative += (float)$row['total'];
        $labels[] = $row['month'];
        $balanceGraph[] = round($cumulative,2);
    }
}