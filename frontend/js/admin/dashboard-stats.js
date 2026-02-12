/**
 * Dashboard — Module Statistiques
 * Gestion de l'onglet statistiques (CA par menu, graphiques Chart.js)
 */

let statsChartInstance = null;

/**
 * Charge la vue statistiques dans le conteneur principal.
 * @param {HTMLElement} container
 * @param {HTMLElement} headerActions
 */
async function loadStatsView(container, headerActions) {
    headerActions.innerHTML = '';
    
    container.innerHTML = `
        <div class="stats-controls">
            <div class="form-group">
                <label>Date Début</label>
                <input type="date" id="stats-start" class="form-input">
            </div>
            <div class="form-group">
                <label>Date Fin</label>
                <input type="date" id="stats-end" class="form-input">
            </div>
            <button class="btn btn--primary" id="btn-refresh-stats">Filtrer</button>
        </div>

        <div>
            <!-- Tableau CA -->
            <div class="card">
                <h3>Chiffre d'Affaires par Menu</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Menu ID</th>
                                <th>Commandes</th>
                                <th>Pers. Total</th>
                                <th>C.A. (€)</th>
                            </tr>
                        </thead>
                        <tbody id="stats-table-body"></tbody>
                    </table>
                </div>
            </div>

            <!-- Graphique -->
            <div class="card">
                 <h3>Chiffre d'Affaires et Commandes</h3>
                 <canvas id="statsChart"></canvas>
            </div>
        </div>
    `;

    document.getElementById('btn-refresh-stats').addEventListener('click', () => {
        fetchStatsData();
    });

    // Load initial data
    fetchStatsData();
}

/**
 * Récupère et affiche les données statistiques.
 */
async function fetchStatsData() {
    const startDate = document.getElementById('stats-start').value;
    const endDate = document.getElementById('stats-end').value;
    
    try {
        const stats = await AdminService.getStats({ startDate, endDate });
        
        // 1. Update Table
        const tbody = document.getElementById('stats-table-body');
        tbody.innerHTML = '';
        
        if (stats.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="data-table__cell--center">Aucune donnée (MongoDB)</td></tr>';
        } else {
            stats.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${item.menuId}</td>
                    <td>${item.totalCommandes}</td>
                    <td>${item.nombrePersonnesTotal}</td>
                    <td><strong>${item.chiffreAffaires.toFixed(2)} €</strong></td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 2. Update Chart
        const ctx = document.getElementById('statsChart').getContext('2d');
        
        const labels = stats.map(item => `Menu #${item.menuId}`);
        const dataCA = stats.map(item => item.chiffreAffaires);
        const dataCount = stats.map(item => item.totalCommandes);

        if (statsChartInstance) {
            statsChartInstance.destroy();
        }

        statsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Chiffre d\'Affaires (€)',
                        data: dataCA,
                        backgroundColor: 'rgba(46, 204, 113, 0.6)',
                        borderColor: 'rgba(39, 174, 96, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Nombre de Commandes',
                        data: dataCount,
                        backgroundColor: 'rgba(52, 152, 219, 0.6)',
                        borderColor: 'rgba(41, 128, 185, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Chiffre d\'Affaires (€)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Commandes' },
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

    } catch (e) {
        console.error("Erreur stats:", e);
    }
}
