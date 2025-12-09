/**
 * merchant_form.js
 *
 *  Description :
 *  Script pour gérer les champs dynamiques du formulaire de gestion des commerçants
 * 
 * - Ajout
 * - Suppression
 * - Modification
 * - Activation / Désactivation
 *
 */

// ===============================
// VARIABLES
// ===============================

const actionSelect = document.getElementById('actionSelect');
const dynamicFields = document.getElementById('dynamicFields');
const formHidden = document.getElementById('action_type');

// ===============================
// FONCTION D'AJOUT, SUPPRESSION, MODIFICATION, ACTIVATION, DESACTIVATION
// ===============================

actionSelect.addEventListener('change', function() {
    const val = this.value;
    formHidden.value = val;
    dynamicFields.innerHTML = '';

    if(val === 'ajouter') {
        dynamicFields.innerHTML = `
        <label>Login *</label><input type="text" name="login" required>
        <label>Mot de passe *</label><input type="text" name="password" required>
        <label>Nom *</label><input type="text" name="nom" required>
        <label>Prénom *</label><input type="text" name="prenom" required>
        <label>Email *</label><input type="email" name="email" required>
        <label>Raison sociale *</label><input type="text" name="raison_sociale" required>
        <label>Ville *</label><input type="text" name="ville" required>
        <label>SIREN *</label><input type="text" name="siren" required>
        `;
    } else if(val === 'supprimer') {
        let options = commercants.map(c => `<option value="${c.siren}">${c.raison_sociale} (${c.nom} ${c.prenom})</option>`).join('');
        dynamicFields.innerHTML = `<label>Choisir commerçant</label>
        <select name="siren" required>
            <option value="">-- Sélectionnez --</option>
            ${options}
        </select>`;
    } else if(val === 'modifier') {
        let options = commercants.map(c => `<option value="${c.siren}" data-nom="${c.nom}" data-prenom="${c.prenom}" data-email="${c.email}" data-raison="${c.raison_sociale}" data-ville="${c.ville}">${c.raison_sociale} (${c.nom} ${c.prenom})</option>`).join('');
        dynamicFields.innerHTML = `
        <label>Choisir commerçant</label>
        <select id="modCommercant" name="siren" required>
            <option value="">-- Sélectionnez --</option>
            ${options}
        </select>
        <div id="modFields" style="margin-top:10px;">
            <label>Nom</label><input type="text" name="nom">
            <label>Prénom</label><input type="text" name="prenom">
            <label>Email</label><input type="email" name="email">
            <label>Raison sociale</label><input type="text" name="raison_sociale">
            <label>Ville</label><input type="text" name="ville">
        </div>`;
        document.getElementById('modCommercant').addEventListener('change', function(){
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
    } else if(val === 'activer' || val === 'desactiver') {
        let options = commercants.map(c => `<option value="${c.id_utilisateur}">${c.raison_sociale} (${c.nom} ${c.prenom})</option>`).join('');
        dynamicFields.innerHTML = `<label>Choisir le commerçant</label>
            <select name="id_utilisateur" required>
                <option value="">-- Sélectionnez --</option>
                ${options}
            </select>`;
    }

});