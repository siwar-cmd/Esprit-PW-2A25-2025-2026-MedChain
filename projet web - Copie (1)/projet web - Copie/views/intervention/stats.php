<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Interventions - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container { display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 30px; }
        .chart-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 45%; min-width: 300px; }
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
                    <a href="#" class="dropbtn active">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li><a href="securite.php">Remplacement</a></li>
                <li><a href="cas_usage.php">Rendez-vous</a></li>
                <li><a href="blog.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>📊 Statistiques des Interventions</h1>
            <a href="index.php?page=intervention" class="btn btn-secondary">← Retour aux Interventions</a>
        </div>

        <div class="stats-container">
            <!-- Diagramme par Niveau d'urgence -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Par Niveau d'Urgence</h3>
                <canvas id="urgenceChart"></canvas>
            </div>

            <!-- Graphique par Type -->
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Par Type</h3>
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblUrg = []; $datUrg = [];
            foreach($stats['urgences'] as $row) {
                $lblUrg[] = "Niveau " . $row['niveauUrgence'];
                $datUrg[] = $row['total'];
            }
            $lblTyp = []; $datTyp = [];
            foreach($stats['types'] as $row) {
                $lblTyp[] = $row['typeIntervention'];
                $datTyp[] = $row['total'];
            }
        ?>

        new Chart(document.getElementById('urgenceChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lblUrg); ?>,
                datasets: [{
                    data: <?php echo json_encode($datUrg); ?>,
                    backgroundColor: ['#ff9999', '#ff6666', '#ff3333', '#cc0000', '#990000']
                }]
            }
        });

        new Chart(document.getElementById('typeChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lblTyp); ?>,
                datasets: [{
                    label: 'Nombre d\'interventions',
                    data: <?php echo json_encode($datTyp); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    </script>
</body>
</html>
