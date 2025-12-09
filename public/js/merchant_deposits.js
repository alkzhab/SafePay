/**
 * merchant_deposits.js
 *
 *  Description :
 *  Script pour gérer les remises des commerçants
 * 
 * - Affichage des détails des remises
 * - Toggle d'affichage
 * - Gestion du menu d'exportation
 * 
 *
 */

// ===============================
// FONCTIONS DE TOGGLE D'AFFICHAGE DES DETAILS
// ===============================

document.addEventListener('DOMContentLoaded', () => {

    // Fermer tous les conteneurs de détails au chargement de la page
    const allDetails = document.querySelectorAll('.details-container');
    allDetails.forEach(container => {
        container.style.display = 'none';
    });

    // Fonction pour basculer l'affichage des détails
    function toggleDetail(container, toggleIcon) {
        const isOpen = container.style.display === 'block';
        container.style.display = isOpen ? 'none' : 'block';
        toggleIcon.textContent = isOpen ? '➕' : '➖';
    }

    // Manipule le clic sur l'icône de bascule
    const toggles = document.querySelectorAll('.toggle-details');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Empêche la propagation du clic à la ligne principale
            const container = toggle.closest('tr').nextElementSibling.querySelector('.details-container');
            toggleDetail(container, toggle);
        });
    });

    // Manipule le clic sur la ligne principale pour basculer les détails
    const mainRows = document.querySelectorAll('.main-row');
    mainRows.forEach(row => {
        row.addEventListener('click', (e) => {
            // Ignore le clic si c'est sur l'icône de bascule
            if (!e.target.classList.contains('toggle-details')) {
                const container = row.nextElementSibling.querySelector('.details-container');
                const toggleIcon = row.querySelector('.toggle-details');
                toggleDetail(container, toggleIcon);
            }
        });
    });

});

// ===============================
// FONCTION D'EXPORTATION DES DONNÉES DE REMISE
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