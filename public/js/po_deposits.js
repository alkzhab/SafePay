/**
 * po_deposits.js
 *
 *  Description :
 *  Script pour gérer les remises
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

$(document).ready(function(){
    $('.toggle-details').click(function(e){
        e.stopPropagation();
        const container = $(this).closest('tr').next('.details-row').find('.details');
        container.slideToggle();
        $(this).text($(this).text()==='➕'?'➖':'➕');
    });
    $('.main-row').click(function(e){
        if(!$(e.target).hasClass('toggle-details')){
            const container = $(this).next('.details-row').find('.details');
            container.slideToggle();
            $(this).find('.toggle-details').text(function(i,text){return text==='➕'?'➖':'➕';});
        }
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