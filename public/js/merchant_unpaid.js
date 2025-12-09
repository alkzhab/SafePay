/**
 * merchant_unpaid.js
 *
 *  Description :
 *  Script pour gérer le graphique des impayés des commerçants
 * 
 * - Affichage du graphique
 * - Changement de type de graphique (ligne/barre)
 * - Export du graphique au format PNG
 * 
 *
 */

// ===============================
// GRAPHIQUE
// ===============================

new Chart(document.getElementById('motifChart').getContext('2d'), {
    type:'pie',
    data: {labels: motifLabels, datasets:[{data: motifData, backgroundColor: motifColors.slice(0,motifLabels.length)}]},
    options:{responsive:true, plugins:{legend:{position:'right'}, tooltip:{callbacks:{label: ctx => ctx.raw.toLocaleString('fr-FR',{minimumFractionDigits:2}) + ' €'}}}}
});

const ctx = document.getElementById('impayesChart').getContext('2d');

let impayesChart = new Chart(ctx, {
    type: chartType,
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Montant impayés (€)',
                data: chartImpayes,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.2)',
                fill: true,
                tension: 0.3
            },
            {
                label: 'Chiffre d\'affaires (€)',
                data: chartCA,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.2)',
                fill: false,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});

// ---- Écouter le select pour changer le type ----
document.getElementById('graphTypeSelect').addEventListener('change', function() {
    const newType = this.value;      // line ou bar
    impayesChart.destroy();           // détruit l'ancien graphique
    impayesChart = new Chart(ctx, {   // recrée avec le nouveau type
        type: newType,
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Montant impayés (€)',
                    data: chartImpayes,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Chiffre d\'affaires (€)',
                    data: chartCA,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.2)',
                    fill: false,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});

// ===============================
// FONCTION D'EXPORTATION DU GRAPHIQUE
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

function exportChart() {
    const motifChartCanvas = document.getElementById('motifChart');

    const link1 = document.createElement('a');
    link1.href = motifChartCanvas.toDataURL('image/png');
    link1.download = 'graph_motif.png';
    link1.click();
}

function exportChart2() {
    const impayesChartCanvas = document.getElementById('impayesChart');

    const link2 = document.createElement('a');
    link2.href = impayesChartCanvas.toDataURL('image/png');
    link2.download = 'graph_impayes.png';
    link2.click();
}

// ===============================
// FONCTION D'EXPORTATION DES GRAPHIQUES DANS LE FORMULAIRE PDF
// ===============================

document.querySelector('.export-dropdown form').addEventListener('submit', function(e){
    const canvas1 = document.getElementById("motifChart");
    const canvas2 = document.getElementById("impayesChart");

    if (canvas1) {
        document.getElementById("chart_image1").value = canvas1.toDataURL("image/png");
    }
    if (canvas2) {
        document.getElementById("chart_image2").value = canvas2.toDataURL("image/png");
    }
});
