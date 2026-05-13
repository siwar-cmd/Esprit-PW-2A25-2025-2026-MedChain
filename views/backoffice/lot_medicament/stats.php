<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';
$ctrl = new LotMedicamentController();

$result = $ctrl->getAllLotMedicaments();
$lots = $result['lots'] ?? [];
$stats = $ctrl->getStats();

// Prepare data for Chart.js
$labels = [];
$initialData = [];
$remainingData = [];

foreach ($lots as $l) {
    $labels[] = $l['nom_medicament'];
    $initialData[] = (int)$l['quantite_initial'];
    $remainingData[] = (int)$l['quantite_restante'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Statistiques Stocks – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../components/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); text-align: center; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .stat-label { font-size: 13px; color: var(--gray-500); font-weight: 500; }
        
        .chart-container { background: white; padding: 32px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); margin-bottom: 32px; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .chart-header h2 { font-family: 'Syne', sans-serif; font-size: 20px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Statistiques des Stocks</h1>
                <p>Analyse comparative des quantités initiales et restantes par lot</p>
            </div>
            <a href="admin-index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à l'inventaire
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_lots'] ?></div>
                <div class="stat-label">Total Lots</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['sum_initial'] ?></div>
                <div class="stat-label">Quantité Initiale Cumulée</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['sum_restante'] ?></div>
                <div class="stat-label">Quantité Restante Globale</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color:var(--red);"><?= $stats['expires'] ?></div>
                <div class="stat-label">Lots Expirés</div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h2>Répartition par Médicament</h2>
                <div style="display:flex; gap:16px; font-size:12px;">
                    <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background:#93c5fd; border-radius:3px;"></span> Quantité Initiale</span>
                    <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background:#6ee7b7; border-radius:3px;"></span> Quantité Restante</span>
                </div>
            </div>
            <div style="height: 400px; position: relative;">
                <canvas id="stockChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
const ctx = document.getElementById('stockChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: 'Quantité Initiale',
                data: <?= json_encode($initialData) ?>,
                backgroundColor: 'rgba(147, 197, 253, 0.8)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 4
            },
            {
                label: 'Quantité Restante',
                data: <?= json_encode($remainingData) ?>,
                backgroundColor: 'rgba(110, 231, 183, 0.8)',
                borderColor: '#10b981',
                borderWidth: 1,
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

</body>
</html>
