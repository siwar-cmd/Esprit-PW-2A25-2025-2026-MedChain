<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Lots - MedChain</title>
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
            <h1>📊 Statistiques des Lots Sensibles</h1>
            <a href="index.php?page=lot" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="stats-container">
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">Quantité Globale par Médicament</h3>
                <canvas id="qteChart"></canvas>
            </div>
            <div class="chart-box">
                <h3 style="text-align: center; margin-bottom: 15px;">État des Péremptions</h3>
                <canvas id="perimChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        <?php
            $lblQ = []; $datQ = [];
            foreach($stats['quantites'] as $row) {
                $lblQ[] = htmlspecialchars($row['nomMedicament']);
                $datQ[] = $row['total'];
            }

            $lblP = []; $datP = []; $colP = [];
            foreach($stats['peremptions'] as $row) {
                // $row['perime'] is 1 if perimé, 0 if valide
                $lblP[] = $row['perime'] ? '⚠️ Périmé' : '✅ Valide';
                $datP[] = $row['nb'];
                $colP[] = $row['perime'] ? '#ff6384' : '#4bc0c0';
            }
        ?>

        new Chart(document.getElementById('qteChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lblQ); ?>,
                datasets: [{ label: 'Quantité totale', data: <?php echo json_encode($datQ); ?>, backgroundColor: '#36a2eb' }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 10 } } } }
        });

        new Chart(document.getElementById('perimChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lblP); ?>,
                datasets: [{ data: <?php echo json_encode($datP); ?>, backgroundColor: <?php echo json_encode($colP); ?> }]
            }
        });
    </script>
</body>
</html>
