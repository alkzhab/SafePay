/**
 * po_dashboard.js
 *
 *  Description :
 *  Script pour gérer l'exportation des données
 * 
 * - Exportation des données
 * 
 *
 */

// ===============================
// EXPORTATION DES DONNÉES
// ===============================

document.addEventListener('click', function(e) {
    const dropdowns = document.querySelectorAll('.export-dropdown');
    dropdowns.forEach(drop => {
        if (drop.contains(e.target)) {
            drop.querySelector('.dropdown-menu').classList.toggle('show');
        } else {
            drop.querySelector('.dropdown-menu').classList.remove('show');
        }
    });
});