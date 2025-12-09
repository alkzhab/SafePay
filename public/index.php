<?php
/**
 * index.php
 *
 * Description:
 * Charge les données via login.php et affiche le template HTML.
 *
 */

require_once __DIR__ . '/../logique/login.php'; // Charger toute la logique
include '../templates/login.html.php'; // Afficher le template