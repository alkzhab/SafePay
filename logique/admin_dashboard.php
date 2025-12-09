<?php
/**
 * admin_dashboard.php
 *
 * Description:
 * Gestion des utilisateurs et des requêtes du Product Owner.
 *
 * - Vérifie la session et le rôle ADMIN
 * - Récupère les requêtes du Product Owner
 * - Gère l'ajout, la modification, la suppression et l'activation/désactivation des commerçants
 * - Enrichit les requêtes avec les informations des utilisateurs concernés
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

$login = $_SESSION['user_login'];
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login LIMIT 1");
$stmt->execute(['login' => $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'ADMIN') {
    header('Location:index.php');
    exit();
}

// ----------------------
// 2. Récupératon des requêtes du Product Owner
// ----------------------

// Récupérer les requêtes envoyées par les PO
$requetes = $pdo->query("SELECT r.id_requete, r.type, r.details, r.date_requete, u.nom, u.prenom 
                         FROM requete r 
                         JOIN po p ON r.id_po = p.id_po 
                         JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                         ORDER BY r.date_requete DESC")->fetchAll(PDO::FETCH_ASSOC);

// Supprimer une requête si le bouton est cliqué
if(isset($_POST['validate_id'])){
    $deleteStmt = $pdo->prepare("DELETE FROM requete WHERE id_requete = :id");
    $deleteStmt->execute(['id' => $_POST['validate_id']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
// Récupérer les commerçants existants
$commercants = $pdo->query("SELECT c.siren, c.raison_sociale, u.id_utilisateur, u.nom, u.prenom, u.email, c.ville 
                            FROM commercant c 
                            JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                            ORDER BY c.raison_sociale ASC")->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// 3. Gestion des actions
// ----------------------

// Message retour
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? '';
    $details = $_POST;

    try {
        if ($action === 'ajouter') {
            // Ajouter utilisateur + commerçant
            $stmtCheck = $pdo->prepare("SELECT * FROM utilisateur WHERE login = ?");
            $stmtCheck->execute([$details['login']]);
            if ($stmtCheck->rowCount() > 0) throw new Exception("Login déjà existant");

            $hashedPassword = password_hash($details['password'], PASSWORD_DEFAULT);

            $stmtUser = $pdo->prepare("INSERT INTO utilisateur (login, mot_de_passe, nom, prenom, email, role, actif) 
                                       VALUES (?, ?, ?, ?, ?, 'COMMERCANT', 1)");
            $stmtUser->execute([$details['login'], $hashedPassword, $details['nom'], $details['prenom'], $details['email']]);
            $idUser = $pdo->lastInsertId();

            $stmtCom = $pdo->prepare("INSERT INTO commercant (siren, id_utilisateur, raison_sociale, ville) 
                                      VALUES (?, ?, ?, ?)");
            $stmtCom->execute([$details['siren'], $idUser, $details['raison_sociale'], $details['ville']]);

            $message = "✔️ Commerçant ajouté avec succès !";

        } elseif ($action === 'supprimer') {
            // Supprimer commerçant
            $stmt = $pdo->prepare("DELETE c, u FROM commercant c JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur WHERE c.siren = ?");
            $stmt->execute([$details['siren']]);
            $message = "✔️ Commerçant supprimé.";

        } elseif ($action === 'modifier') {
            // Modifier commerçant
            $stmtCom = $pdo->prepare("UPDATE commercant c JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                                      SET u.nom=?, u.prenom=?, u.email=?, c.raison_sociale=?, c.ville=?
                                      WHERE c.siren=?");
            $stmtCom->execute([
                $details['nom'], $details['prenom'], $details['email'], 
                $details['raison_sociale'], $details['ville'], $details['siren']
            ]);
            $message = "✔️ Commerçant modifié.";
        } elseif ($action === 'activer' || $action === 'desactiver') {
            // Trouver l'utilisateur sélectionné
            $idUser = $details['id_utilisateur'] ?? null;
            if (!$idUser) throw new Exception("Utilisateur non sélectionné");

            $newStatus = ($action === 'activer') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE utilisateur SET actif = ? WHERE id_utilisateur = ?");
            $stmt->execute([$newStatus, $idUser]);

            $message = ($action === 'activer') ? "✔️ Compte activé." : "✔️ Compte désactivé.";
        }

    } catch (Exception $e) {
        $message = "❌ " . $e->getMessage();
    }
}

// ----------------------
// 4. Enrichissement des requêtes
// ----------------------

$usersCache = [];

foreach ($requetes as $r) {
    $details = json_decode($r['details'], true);

    if (isset($details['id_utilisateur'])) {

        $idUser = $details['id_utilisateur'];

        if (!isset($usersCache[$idUser])) {

            $stmt = $pdo->prepare("
                SELECT u.id_utilisateur, u.login, u.nom, u.prenom,
                       u.email, u.role, u.actif,
                       c.raison_sociale, c.siren
                FROM utilisateur u
                LEFT JOIN commercant c ON u.id_utilisateur = c.id_utilisateur
                WHERE u.id_utilisateur = ?
                LIMIT 1
            ");
            $stmt->execute([$idUser]);
            $usersCache[$idUser] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}