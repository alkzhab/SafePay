/**
 * po_account.js
 *
 *  Description :
 *  Script pour gérer les requêtes de gestion des commerçants par le PO
 * 
 * - Ajout
 * - Suppression
 * - Modification
 * - Activation / Désactivation
 * 
 *
 */

// ===============================
// FONCTION D'AJOUT, SUPPRESSION, MODIFICATION, ACTIVATION, DESACTIVATION
// ===============================

const typeSelect = document.getElementById('type_requete');
const dynamicFields = document.getElementById('dynamicFields');

typeSelect.addEventListener('change', function() {
    dynamicFields.innerHTML = '';

    if (this.value === 'ajouter_commercant') {
        dynamicFields.innerHTML = `
            <label>Nom</label><input type="text" name="nom" required>
            <label>Prénom</label><input type="text" name="prenom" required>
            <label>Email</label><input type="email" name="email" required>
            <label>SIREN</label><input type="text" name="siren" required>
            <label>Raison sociale</label><input type="text" name="raison_sociale" required>
            <label>Ville</label><input type="text" name="ville" required>
        `;
    } else if (this.value === 'supprimer_commercant' || this.value === 'activer_compte' || this.value === 'desactiver_compte') {
        let options = commercants.map(c => `<option value="${c.id_utilisateur}">${c.raison_sociale} (${c.nom} ${c.prenom})</option>`).join('');
        dynamicFields.innerHTML = `
            <label>Choisir le commerçant</label>
            <select name="id_utilisateur" required>
                <option value="">-- Sélectionnez --</option>
                ${options}
            </select>
        `;
    } else if (this.value === 'modifier_commercant') {
        let options = commercants.map(c => `<option value="${c.siren}" data-nom="${c.nom}" data-prenom="${c.prenom}" data-email="${c.email}" data-raison="${c.raison_sociale}" data-ville="${c.ville}">${c.raison_sociale} (${c.nom} ${c.prenom})</option>`).join('');
        dynamicFields.innerHTML = `
            <label>Choisir le commerçant à modifier</label>
            <select id="modCommercant" name="siren" required>
                <option value="">-- Sélectionnez --</option>
                ${options}
            </select>
            <div id="modFields" style="margin-top:15px;">
                <label>Nom</label><input type="text" name="nom">
                <label>Prénom</label><input type="text" name="prenom">
                <label>Email</label><input type="email" name="email">
                <label>Raison sociale</label><input type="text" name="raison_sociale">
                <label>Ville</label><input type="text" name="ville">
            </div>
        `;
        const modSelect = document.getElementById('modCommercant');
        modSelect.addEventListener('change', function() {
            const sel = this.selectedOptions[0];
            if(sel.value){
                document.querySelector('input[name="nom"]').value = sel.dataset.nom;
                document.querySelector('input[name="prenom"]').value = sel.dataset.prenom;
                document.querySelector('input[name="email"]').value = sel.dataset.email;
                document.querySelector('input[name="raison_sociale"]').value = sel.dataset.raison;
                document.querySelector('input[name="ville"]').value = sel.dataset.ville;
            } else {
                document.querySelector('input[name="nom"]').value = '';
                document.querySelector('input[name="prenom"]').value = '';
                document.querySelector('input[name="email"]').value = '';
                document.querySelector('input[name="raison_sociale"]').value = '';
                document.querySelector('input[name="ville"]').value = '';
            }
        });
    }
});