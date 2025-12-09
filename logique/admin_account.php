<?php
/**
 * admin_account.php
 *
 * Description:
 * Gestion de la page d'administration des comptes utilisateurs.
 *
 * - Vérifie la session et le rôle ADMIN
 * - Récupère les utilisateurs
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
// 2. Récupération des utilisateurs
// ----------------------

// Récupérer tous les utilisateurs
$users = $pdo->query("SELECT id_utilisateur, login, nom, prenom, email, role, actif FROM utilisateur ORDER BY role, nom")->fetchAll(PDO::FETCH_ASSOC);