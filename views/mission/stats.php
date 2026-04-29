<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Missions - MedChain</title>
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
                <li class="dropdown">
                    <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Traçabilité ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=lot">Lots Médicaments</a>
                        <a href="index.php?page=distribution">Distributions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv">Agenda RDV</a>
                        <a href="index.php?page=ficherdv">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="loisir.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>📊 Statistiques des Missions</h1>
            <a href="index.php?page=mission" class="btn btn-secondary">← Retour aux Missions</a>
        </div>

        <div class="stats-container">
            <!-- Box 1: Missions par Ambulance -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Missions par Ambulance (Top 5)</h3>
                <canvas id="ambulanceChart"></canvas>
            </div>

            <!-- Box 2: Statut des Missions -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Répartition par Statut</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        // Préparation des données depuis PHP
        <?php
            $labelsAmbulances = [];
            $dataAmbulances = [];
            foreach($stats['top_ambulances'] as $row) {
                $labelsAmbulances[] = $row['immatriculation'];
                $dataAmbulances[] = $row['total'];
            }
            
            $terminees = $stats['status_missions']['terminees'] ?? 0;
            $en_cours = $stats['status_missions']['en_cours'] ?? 0;
        ?>

        // Chart 1: Bar Chart (Top Ambulances)
        const ctxAmb = document.getElementById('ambulanceChart').getContext('2d');
        new Chart(ctxAmb, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labelsAmbulances); ?>,
                datasets: [{
                    label: 'Nombre de missions',
                    data: <?php echo json_encode($dataAmbulances); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        // Chart 2: Pie Chart (Statuts)
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: ['Terminées', 'En cours'],
                datasets: [{
                    data: [<?php echo $terminees; ?>, <?php echo $en_cours; ?>],
                    backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 206, 86, 0.6)'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
