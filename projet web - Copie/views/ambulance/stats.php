<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Ambulances - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 45%;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php"><img src="logo.PNG" alt="MedChain Logo"></a></div>
            <ul class="nav-links">
                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>
                <li><a href="#">Bloc Opératoire</a></li>
                <li><a href="#">Remplacement</a></li>
                <li><a href="#">Rendez-vous</a></li>
                <li><a href="#">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>📊 Statistiques des Ambulances</h1>
            <a href="index.php?page=ambulance" class="btn btn-secondary">← Retour aux Ambulances</a>
        </div>

        <div class="stats-container">
            <!-- Box 1: Ambulances par Statut -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Répartition par Statut</h3>
                <canvas id="ambulanceStatusChart"></canvas>
            </div>

            <!-- Box 2: Ambulances par Disponibilité -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Disponibilité Immédiate</h3>
                <canvas id="ambulanceDispoChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        // Préparation des données depuis PHP
        <?php
            $labels = [];
            $data = [];
            // Map des couleurs selon le nom du statut
            $colors = [];
            foreach($stats['status_count'] as $row) {
                $labels[] = $row['statut'];
                $data[] = $row['total'];
                if(strtolower($row['statut']) == 'en service') $colors[] = 'rgba(75, 192, 192, 0.6)';
                else if(strtolower($row['statut']) == 'en maintenance') $colors[] = 'rgba(255, 206, 86, 0.6)';
                else $colors[] = 'rgba(255, 99, 132, 0.6)';
            }
            $dispo = $stats['dispo_count']['dispo'];
            $indispo = $stats['dispo_count']['indispo'];
        ?>

        // Chart 1: Pie Chart (Statuts)
        const ctxAmbStatus = document.getElementById('ambulanceStatusChart').getContext('2d');
        new Chart(ctxAmbStatus, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: <?php echo json_encode($colors); ?>,
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });

        // Chart 2: Pie Chart (Dispo)
        const ctxAmbDispo = document.getElementById('ambulanceDispoChart').getContext('2d');
        new Chart(ctxAmbDispo, {
            type: 'pie',
            data: {
                labels: ['Disponible', 'Non Disponible'],
                datasets: [{
                    data: [<?php echo $dispo; ?>, <?php echo $indispo; ?>],
                    backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(201, 203, 207, 0.6)'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
