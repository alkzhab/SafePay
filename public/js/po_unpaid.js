/**
 * merchant_dashboard.js
 *
 *  Description :
 *  Script pour gérer le graphique du camembert des impayés par motif
 * 
 * - Affichage du graphique
 * - Export du graphique au format PNG
 * - Exportation des données
 * 
 *
 */

// ===============================
// GRAPHIQUE
// ===============================

const colors = [
    '#ef4444','#f59e0b','#3b82f6','#10b981',
    '#8b5cf6','#f472b6','#14b8a6','#f97316'
];

new Chart(document.getElementById('motifChart'), {
    type: 'pie',
    data: {
        labels: motifLabels,
        datasets: [{
            data: motifData,
            backgroundColor: colors.slice(0, motifLabels.length)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.raw.toLocaleString('fr-FR', { minimumFractionDigits:2 }) + ' €'
                }
            }
        }
    }
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
    const canvas = document.getElementById("motifChart");
    const link = document.createElement("a");
    link.href = canvas.toDataURL("image/png");
    link.download = "repartition_impayes.png";
    link.click();
}

document.querySelector('.export-dropdown form')
    .addEventListener('submit', function () {
        const canvas = document.getElementById("motifChart");
        document.getElementById("chart_image").value =
            canvas.toDataURL("image/png");
});
