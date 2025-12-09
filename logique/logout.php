<?php
/**
 * logout.php
 *
 * Description:
 * Gestion de la déconnexion utilisateur
 *
 * - Détruit la session utilisateur
 * - Redirige vers la page de connexion
 *
 * 
 */

// ----------------------
// 1. Destruction de la session
// ----------------------

session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si il existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// ----------------------
// 2. Redirection vers la page de connexion
// ----------------------

// Rediriger vers la page de connexion
header('Location: index.php');
exit();
?>