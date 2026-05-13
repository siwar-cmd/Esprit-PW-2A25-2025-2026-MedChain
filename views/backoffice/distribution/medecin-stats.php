<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/DistributionController.php';
require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';

$distCtrl = new DistributionController();
$lotCtrl = new LotMedicamentController();

$distStats = $distCtrl->getStats();
$lotsData = $lotCtrl->getAllLotMedicaments();
$lots = $lotsData['success'] ? $lotsData['lots'] : [];

// Data for Chart: Distributions by Medication Name
$distributions = $distCtrl->getAllDistributions()['distributions'] ?? [];
$counts = [];
foreach ($distributions as $d) {
    if ($d['statut'] === 'Accepte') {
        $name = $d['nom_medicament'];
        $counts[$name] = ($counts[$name] ?? 0) + (int)$d['quantite_distribuee'];
    }
}

$labels = array_keys($counts);
$dataValues = array_values($counts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Analyses des Distributions – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../components/medecin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); box-shadow: var(--shadow-sm); }
        .stat-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 700; color: var(--navy); }
        .stat-label { font-size: 13px; color: var(--gray-500); }
        
        .chart-container { background: white; padding: 32px; border-radius: var(--radius-lg); border: 1px solid rgba(29,158,117,.15); margin-bottom: 32px; }
        .chart-title { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 24px; color: var(--navy); }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Analyses & Statistiques</h1>
                <p>Visualisation des consommations de médicaments</p>
            </div>
            <a href="medecin-index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour au registre
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;"><i class="bi bi-clipboard-data"></i></div>
                    <div class="stat-label">Distributions Totales</div>
                </div>
                <div class="stat-value"><?= $distStats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-check2-all"></i></div>
                <div class="stat-label">Unités Distribuées</div>
                <div class="stat-value"><?= $distStats['sum_distribuee'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f3e8ff; color:#7e22ce;"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-label">Ce mois-ci</div>
                <div class="stat-value"><?= $distStats['ce_mois'] ?></div>
            </div>
        </div>

        <div class="chart-container">
            <h2 class="chart-title"><i class="bi bi-pie-chart-fill" style="color:var(--green);"></i> Consommation par Médicament (Unités)</h2>
            <div style="height: 350px; position: relative;">
                <canvas id="distChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>État des Stocks par Lot</h2></div>
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th>Lot #</th>
                            <th>Restant</th>
                            <th>Progression</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lots as $l): 
                            $percent = round(($l['quantite_restante'] / $l['quantite_initial']) * 100);
                            $color = ($percent < 20) ? '#ef4444' : (($percent < 50) ? '#f59e0b' : '#10b981');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($l['nom_medicament']) ?></strong></td>
                            <td><code style="font-size:12px;">LOT-<?= $l['id_lot'] ?></code></td>
                            <td><?= $l['quantite_restante'] ?> / <?= $l['quantite_initial'] ?></td>
                            <td>
                                <div style="width:100%; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                    <div style="width:<?= $percent ?>%; height:100%; background:<?= $color ?>;"></div>
                                </div>
                                <small style="font-size:10px; color:var(--gray-500);"><?= $percent ?>% disponible</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
const ctx = document.getElementById('distChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Unités distribuées',
            data: <?= json_encode($dataValues) ?>,
            backgroundColor: 'rgba(29, 158, 117, 0.7)',
            borderColor: '#1D9E75',
            borderWidth: 1,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

</body>
</html>
