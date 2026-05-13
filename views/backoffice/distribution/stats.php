<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'medecin'])) {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/DistributionController.php';

$distController = new DistributionController();
$stats = $distController->getStats();
$distributionsData = $distController->getAllDistributions()['distributions'] ?? [];

// Préparer les données pour le graphique (Ex: Total distribué par lot)
$aggregatedData = [];
foreach ($distributionsData as $dist) {
    $nom = $dist['nom_medicament'];
    if (!isset($aggregatedData[$nom])) {
        $aggregatedData[$nom] = 0;
    }
    $aggregatedData[$nom] += $dist['quantite_distribuee'];
}

$labels = array_keys($aggregatedData);
$quantites = array_values($aggregatedData);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Distributions - Médecin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../components/medecin.css">
    <style>
        .stats-container { max-width: 900px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { color: var(--navy); font-family: 'Syne', sans-serif; }
        .btn { padding: 10px 20px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary { background: #6B7280; }
        .btn-primary { background: var(--green); }
        
        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8fafc; padding: 20px; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--gray-200); }
        .card h3 { font-size: 24px; color: var(--navy); margin-bottom: 5px; }
        .card p { color: #64748B; font-size: 14px; }
        .chart-container { position: relative; height: 400px; width: 100%; margin-top: 20px; }
        
        .pdf-header { display: none; }

        @media print {
            .dashboard-sidebar, .btn, .header div { display: none !important; }
            .dashboard-container { display: block; }
            .dashboard-main { padding: 0; background: white; }
            .stats-container { box-shadow: none; border: none; padding: 0; }
            .pdf-header { 
                display: flex; justify-content: space-between; align-items: center; 
                margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #1D9E75;
            }
            .pdf-logo { display: flex; align-items: center; gap: 15px; }
            .pdf-logo-icon { width: 50px; height: 50px; background: #1D9E75; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
            .pdf-title { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: #1E3A52; }
            .pdf-meta { text-align: right; font-size: 12px; color: #6B7280; line-height: 1.6; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-medecin.php'; ?>
    <main class="dashboard-main">
        <div class="pdf-header">
            <div class="pdf-logo">
                <div class="pdf-logo-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="pdf-title">MedChain</div>
            </div>
            <div class="pdf-meta">
                Date: <?= date('d/m/Y') ?><br>
                Généré par MedChain System<br>
                Rapport Statistique des Distributions
            </div>
        </div>
        <div class="stats-container">
        <div class="header">
            <h1>Statistiques des Distributions</h1>
            <div>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimer</button>
                <a href="medecin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>

        <div class="stats-cards">
            <div class="card">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Distributions</p>
            </div>
            <div class="card">
                <h3 style="color: var(--green);"><?= $stats['sum_distribuee'] ?></h3>
                <p>Quantité Totale Distribuée</p>
            </div>
            <div class="card">
                <h3 style="color: #0284C7;"><?= $stats['ce_mois'] ?></h3>
                <p>Distributions Ce Mois-ci</p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="distChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('distChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Quantité Distribuée',
                    data: <?= json_encode($quantites) ?>,
                    backgroundColor: [
                        'rgba(29, 158, 117, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Répartition des quantités distribuées par lot de médicament'
                    }
                }
            }
        });
    </script>
        </div>
    </main>
</div>
</body>
</html>
