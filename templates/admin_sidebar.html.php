<?php
// Définir la page courante si ce n'est pas déjà fait
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF'], ".php");
}
?>

<div class="sidebar">
    <div class="logo-container">
        <img src="img/logo.png" alt="Logo SafePay" class="logo">
    </div>

    <nav class="nav-links">
        <a href="admin_account.php" class="<?= $currentPage == 'admin_account' ? 'active' : '' ?>">
            <span class="icon"><img src="img/icone_compte.png"></span>
            <span class="text">Compte</span>
        </a>

        <a href="admin_dashboard.php" class="<?= $currentPage == 'admin_dashboard' ? 'active' : '' ?>">
            <span class="icon"><img src="img/icone_tresorerie.png"></span>
            <span class="text">Tableau de bord</span>
        </a>

        <a href="logout.php" class="logout">
            <span class="icon"><img src="img/icone_deconnexion.png"></span>
            <span class="text">Se déconnecter</span>
        </a>
    </nav>
</div>

<style>
body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    display: flex;
    background-color: #f4f6fb;
}

.sidebar {
    width: 200px;
    background: linear-gradient(to bottom, #3a5bcc, #2b45a0);
    color: #fff;
    display: flex;
    flex-direction: column;
    padding: 20px;
    position: fixed;
    height: 100vh;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: width 0.3s;
}

.sidebar .logo-container {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
}

.sidebar .logo {
    width: 180px;
}

.nav-links {
    display: flex;
    flex-direction: column;
}

.nav-links a {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    font-weight: 500;
    color: white;
    text-decoration: none;
    transition: background 0.3s, transform 0.2s;
}

.nav-links a .icon img {
    width: 24px;
    height: 24px;
    margin-right: 15px;
    filter: brightness(1.2);
}

.nav-links a:hover {
    background-color: rgba(255,255,255,0.15);
    transform: translateX(5px);
}

.nav-links a.active {
    background-color: rgba(255,255,255,0.25);
    font-weight: 700;
}

.nav-links a.logout {
    margin-top: auto;
    background-color: rgba(255, 0, 0, 0.2);
}

.nav-links a.logout:hover {
    background-color: rgba(255, 0, 0, 0.35);
}

.content {
    margin-left: 260px;
    padding: 30px;
    flex: 1;
}
</style>