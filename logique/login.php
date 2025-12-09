<?php
/**
 * login.php
 *
 * Description:
 * Gestion de la page de connexion
 *
 * - Si déjà connecté, redirige vers le tableau de bord approprié
 * - Traite le formulaire de connexion
 * - Gère les tentatives de connexion et le blocage de compte
 * - Initialise la session utilisateur
 *
 * 
 */

// ----------------------
// 1. Vérification de session et rôle
// ----------------------

session_start();

// Si déjà connecté, renvoi vers la page d'accueil
if (isset($_SESSION['user_login'])) {
    if ($_SESSION['user_role'] == "COMMERCANT") {
        header("Location: merchant_dashboard.php");
    } else if ($_SESSION['user_role'] == "PO") {
        header("Location: po_dashboard.php");
    } else if ($_SESSION['user_role'] == "ADMIN") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location:index.php");
    }
}

// ----------------------
// 2. Traitement du formulaire de connexion
// ----------------------

$erreur = "";

// Lorsque le formulaire est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once __DIR__ . '/../config/db.php';

    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['mot_de_passe'] ?? '');

    if ($login === '' || $password === '') {
        $erreur = "Veuillez remplir tous les champs.";

    } else {

        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login LIMIT 1");
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            // Vérifier si le compte est bloqué
            if ($user['tentatives_connexion'] >= 3 || $user['actif'] == 0) {
                $erreur = "Votre compte est bloqué. Contactez l'administrateur.";

            } else {

                // Vérification mot de passe
                if (strpos($user['mot_de_passe'], '$2y$') === 0) {
                    $passwordCorrect = password_verify($password, $user['mot_de_passe']);
                } else {
                    $passwordCorrect = ($password === $user['mot_de_passe']); 
                }

                if ($passwordCorrect) {

                    // Réinitialiser des tentatives de connexion + mise à jour dernière connexion
                    $update = $pdo->prepare("
                        UPDATE utilisateur 
                        SET tentatives_connexion = 0, derniere_connexion = NOW() 
                        WHERE id_utilisateur = :id
                    ");
                    $update->execute(['id' => $user['id_utilisateur']]);

                    session_regenerate_id(true);
                    unset($user['mot_de_passe']);
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['user_role'] = $user['role'];

                    // Redirection selon les rôles
                    if ($user['role'] === 'COMMERCANT') {
                        header("Location: merchant_dashboard.php");
                    } elseif ($user['role'] === 'PO') {
                        header("Location: po_dashboard.php");
                    } elseif ($user['role'] === 'ADMIN') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();

                } else {

                    // ----------------------
                    // 3. Gestion des tentatives de connexion
                    // ----------------------

                    // Si le mot de passe incorect, incrémenter les tentatives de connexion
                    $tentative = $user['tentatives_connexion'] + 1;

                    $update = $pdo->prepare("
                        UPDATE utilisateur 
                        SET tentatives_connexion = :tentatives 
                        WHERE id_utilisateur = :id
                    ");
                    $update->execute(['tentatives' => $tentative, 'id' => $user['id_utilisateur']]);

                    if ($tentative == 2) {
                        $erreur = "ATTENTION : C'est votre dernier essai…";
                    } elseif ($tentative >= 3) { // Bloquer le compte
                        $block = $pdo->prepare("
                            UPDATE utilisateur 
                            SET actif = 0 
                            WHERE id_utilisateur = :id
                        ");
                        $block->execute(['id' => $user['id_utilisateur']]);

                        $erreur = "Votre compte a été bloqué après 3 tentatives échouées. Contactez l'administrateur.";
                    } else {
                        $erreur = "Identifiants incorrects. Tentative n°$tentative";
                    }
                }
            }

        } else {
            $erreur = "Identifiants incorrects.";
        }
    }
}