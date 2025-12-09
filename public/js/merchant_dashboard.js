/**
 * merchant_dashboard.js
 *
 *  Description :
 *  Script pour gérer le graphique du tableau de bord des commerçants
 * 
 * - Affichage du graphique
 * - Export du graphique au format PNG
 * 
 *
 */

// ===============================
// GRAPHIQUE
// ===============================

const ctx = document.getElementById('balanceChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labelsFromPHP,
        datasets: [{
            label: "Balance (€)",
            data: dataFromPHP,
            backgroundColor: "rgba(58,91,204,0.7)",
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.parsed.y + " €"
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: val => val + " €",
                    color: "#2b3a9a",
                    font: { weight: '500' }
                },
                grid: { color: "#e0e0e0" }
            },
            x: {
                ticks: { color: "#2b3a9a", font: { weight: '500' } },
                grid: { display: false }
            }
        }
    }
});

// ===============================
// FONCTION D'EXPORTATION DU GRAPHIQUE
// ===============================

function exportBalanceChart() {
    const canvas = document.getElementById("balanceChart");
    const link = document.createElement("a");
    link.href = canvas.toDataURL("image/png");
    link.download = "balance_chart.png";
    link.click();
}
