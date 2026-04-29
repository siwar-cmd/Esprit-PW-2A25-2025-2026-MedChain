<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques RDV - MedChain</title>
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
            <h1>📊 Statistiques des Rendez-Vous</h1>
            <a href="index.php?page=rdv" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="stats-container">
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Statut des RDV</h3>
                <canvas id="statutChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Par Type de Consultation</h3>
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblSt = []; $datSt = []; $colSt = [];
            foreach($stats['statuts'] as $row) {
                $lblSt[] = strtoupper($row['statut']);
                $datSt[] = $row['total'];
                if($row['statut'] == 'confirme') $colSt[] = '#4bc0c0';
                else if($row['statut'] == 'annule') $colSt[] = '#ff6384';
                else if($row['statut'] == 'reporte') $colSt[] = '#ffcd56';
                else $colSt[] = '#c9cbcf'; // planifie par défaut
            }

            $lblT = []; $datT = [];
            foreach($stats['types'] as $row) {
                $lblT[] = $row['typeConsultation'];
                $datT[] = $row['total'];
            }
        ?>

        new Chart(document.getElementById('statutChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($lblSt); ?>,
                datasets: [{ data: <?php echo json_encode($datSt); ?>, backgroundColor: <?php echo json_encode($colSt); ?> }]
            }
        });

        new Chart(document.getElementById('typeChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lblT); ?>,
                datasets: [{ label: 'Nombre de RDV', data: <?php echo json_encode($datT); ?>, backgroundColor: 'rgba(54, 162, 235, 0.6)' }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    </script>
</body>
</html>
