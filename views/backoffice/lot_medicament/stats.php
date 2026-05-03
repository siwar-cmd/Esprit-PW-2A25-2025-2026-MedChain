<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../controllers/LotMedicamentController.php';

$lotController = new LotMedicamentController();
$stats = $lotController->getStats();
$lotsData = $lotController->getAllLotMedicaments()['lots'] ?? [];

// Préparer les données pour le graphique
$labels = [];
$quantites = [];
$restantes = [];
foreach ($lotsData as $lot) {
    $labels[] = $lot['nom_medicament'];
    $quantites[] = $lot['quantite_initial'];
    $restantes[] = $lot['quantite_restante'];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Lots - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../components/admin.css">
    <style>
        .stats-container { max-width: 900px; margin: 0 auto; background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { color: var(--navy); font-family: 'Syne', sans-serif; }
        .btn { padding: 10px 20px; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary { background: #6B7280; }
        .btn-primary { background: var(--green); }
        
        .stats-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8fafc; padding: 20px; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--gray-200); }
        .card h3 { font-size: 24px; color: var(--navy); margin-bottom: 5px; }
        .card p { color: #64748B; font-size: 14px; }
        .chart-container { position: relative; height: 400px; width: 100%; margin-top: 20px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../components/sidebar-admin.php'; ?>
    <main class="dashboard-main">
        <div class="stats-container">
        <div class="header">
            <h1>Statistiques des Lots de Médicaments</h1>
            <div>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimer</button>
                <a href="admin-index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>

        <div class="stats-cards">
            <div class="card">
                <h3><?= $stats['total_lots'] ?></h3>
                <p>Total Lots</p>
            </div>
            <div class="card">
                <h3><?= $stats['sum_initial'] ?></h3>
                <p>Quantité Initiale Cumulée</p>
            </div>
            <div class="card">
                <h3 style="color: var(--green);"><?= $stats['sum_restante'] ?></h3>
                <p>Quantité Restante Globale</p>
            </div>
            <div class="card">
                <h3 style="color: #EF4444;"><?= $stats['expires'] ?></h3>
                <p>Lots Expirés</p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="lotsChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('lotsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'Quantité Initiale',
                        data: <?= json_encode($quantites) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Quantité Restante',
                        data: <?= json_encode($restantes) ?>,
                        backgroundColor: 'rgba(29, 158, 117, 0.7)',
                        borderColor: 'rgb(29, 158, 117)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
        </div>
    </main>
</div>
</body>
</html>
