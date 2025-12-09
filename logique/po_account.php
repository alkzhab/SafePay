<?php
/**
 * po_account.php
 *
 * Description:
 * Gestion des comptes des utilisateurs
 *
 * - Vérifie la session et le rôle PO
 * - Récupère les informations du PO connecté
 * - Récupère la liste des commerçants pour les formulaires
 * - Gère l'envoi des requêtes à l'administrateur pour la gestion des commerçants
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
// 2. Récupération des informations du PO connecté
// ----------------------

// Récupérer l'id_po depuis la table PO
$stmtPo = $pdo->prepare("SELECT id_po FROM po WHERE id_utilisateur = :id_utilisateur LIMIT 1");
$stmtPo->execute(['id_utilisateur' => $_SESSION['user_id']]);
$poRow = $stmtPo->fetch(PDO::FETCH_ASSOC);

if (!$poRow) {
    die("Erreur : PO introuvable dans la table PO.");
}

$id_po = $poRow['id_po'];

// ----------------------
// 3. Récupération de la liste des commerçants pour les formulaires
// ----------------------

// Récupérer la liste des commerçants pour les selects
$stmt = $pdo->query("SELECT c.siren, c.raison_sociale, c.ville, u.nom, u.prenom, u.email, u.id_utilisateur 
                     FROM commercant c 
                     JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                     ORDER BY c.raison_sociale ASC");
$commercants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreur = '';
$success = '';

// ----------------------
// 4. Gestion de l'envoi des requêtes à l'administrateur
// ----------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type_requete = $_POST['type_requete'] ?? '';
    $detailsArray = [];

    switch($type_requete){
        case 'ajouter_commercant':
        case 'modifier_commercant':
            foreach(['nom','prenom','email','siren','raison_sociale','ville'] as $field){
                $detailsArray[$field] = $_POST[$field] ?? '';
            }
            break;
        case 'supprimer_commercant':
        case 'activer_compte':
        case 'desactiver_compte':
            // Pour ces actions, on a juste besoin de l'id_utilisateur
            $detailsArray['id_utilisateur'] = $_POST['id_utilisateur'] ?? '';
            break;
        default:
            $erreur = "Type de requête invalide.";
            break;
    }

    if (!$erreur) {
        $details = json_encode($detailsArray);

        $stmt = $pdo->prepare("INSERT INTO requete (id_po, type, date_requete, details) VALUES (:id_po, :type, NOW(), :details)");
        $stmt->execute([
            'id_po' => $id_po,
            'type' => $type_requete,
            'details' => $details
        ]);

        $success = "La requête a été envoyée à l'administrateur.";
    }
}